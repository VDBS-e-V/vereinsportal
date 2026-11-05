<?php

namespace App\Modules\Forms\Services;

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\Capability;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\AccessGovernance\Support\Capabilities;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Forms\Mail\FormStatusMail;
use App\Modules\Forms\Models\CostCenter;
use App\Modules\Forms\Models\FormApplication;
use App\Modules\Forms\Models\FormEvent;
use App\Modules\Forms\Models\FormItem;
use App\Modules\Forms\Support\FormTypes;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Support\Money;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Gemeinsamer Ablauf aller Anträge: Entwurf, Einreichen, Prüfung, Rückfrage, Freigabe, Ablehnung, Auszahlung. Der Server rechnet die Beträge;
 * Fehler kommen als DomainException mit verständlichem Text.
 */
final class FormApplications
{
    public function __construct(
        private readonly FormNumbers $numbers,
        private readonly ApprovalPolicy $policy,
        private readonly FormFiles $files,
        private readonly FormSettings $settings,
    ) {}

    public function create(User $user, string $type): FormApplication
    {
        if (! in_array($type, FormTypes::enabled(), true)) {
            throw new DomainException('Diese Art von Antrag gibt es noch nicht.');
        }

        $user->loadMissing('person');

        $application = FormApplication::query()->create([
            'number' => $this->numbers->next($type),
            'type' => $type,
            'applicant_user_id' => $user->id,
            'applicant_name' => $user->person?->fullName() ?: (string) $user->email,
            'applicant_email' => $user->email,
            'status' => 'draft',
            'data' => [],
        ]);

        $this->event($application, 'created', $user);

        return $application;
    }

    // ---------- Entwurf ----------

    /**
     * Speichert den Entwurf. Unvollständige Angaben sind erlaubt; falsche Formate nicht.
     *
     * @param  array<string, mixed>  $input  purpose, seminar_id, cost_center, items[<n>][id|date|kind|description|amount], account_holder, iban, declaration
     * @param  array<int|string, UploadedFile>  $uploads  Beleg je Positionsnummer
     *
     * @throws DomainException
     */
    public function saveDraft(FormApplication $application, array $input, array $uploads = []): FormApplication
    {
        if (! $application->isEditable()) {
            throw new DomainException('Dieser Antrag lässt sich nicht mehr ändern.');
        }

        $seminarId = filled($input['seminar_id'] ?? null) ? (int) $input['seminar_id'] : null;

        if ($seminarId !== null && ! Seminar::query()->whereKey($seminarId)->exists()) {
            throw new DomainException('Das gewählte Seminar gibt es nicht.');
        }

        $costCenter = filled($input['cost_center'] ?? null) ? (string) $input['cost_center'] : null;

        if ($costCenter !== null && ! CostCenter::query()->where('code', $costCenter)->where('active', true)->exists()) {
            throw new DomainException('Die gewählte Kostenstelle gibt es nicht.');
        }

        $iban = trim((string) ($input['iban'] ?? ''));

        if ($iban !== '' && ! Iban::valid($iban)) {
            throw new DomainException('Die IBAN ist nicht gültig. Bitte prüfen Sie Länderkennung und Ziffern.');
        }

        $rows = $this->parseRows((array) ($input['items'] ?? []));

        DB::transaction(function () use ($application, $input, $seminarId, $costCenter, $iban, $rows, $uploads): void {
            $keep = [];

            foreach ($rows as $row) {
                $item = isset($row['id']) ? $application->items()->whereKey($row['id'])->first() : null;
                $data = ['position' => $row['position'], 'date' => $row['date'], 'kind' => $row['kind'], 'description' => $row['description'], 'quantity' => 1, 'unit_cents' => $row['amount_cents'], 'amount_cents' => $row['amount_cents']];

                if ($item !== null) {
                    $item->forceFill($data)->save();
                } else {
                    $item = $application->items()->create($data);
                }

                $keep[] = $item->id;

                if (isset($uploads[$row['key']]) && $uploads[$row['key']] instanceof UploadedFile) {
                    $this->files->add($application, $uploads[$row['key']], $item);
                }
            }

            foreach ($application->items()->whereNotIn('id', $keep)->get() as $removed) {
                foreach ($removed->files as $file) {
                    $this->files->remove($file);
                }

                $removed->delete();
            }

            $data = (array) $application->data;
            $data['declaration'] = ! empty($input['declaration']);

            $application->forceFill([
                'purpose' => Str::limit(trim((string) ($input['purpose'] ?? '')), 300, ''),
                'seminar_id' => $seminarId,
                'cost_center' => $costCenter,
                'iban_encrypted' => $iban !== '' ? Iban::normalize($iban) : $application->iban_encrypted,
                'account_holder' => filled($input['account_holder'] ?? null) ? Str::limit(trim((string) $input['account_holder']), 150, '') : $application->account_holder,
                'data' => $data,
                'total_cents' => (int) $application->items()->sum('amount_cents'),
            ])->save();
        });

        return $application->refresh();
    }

    /**
     * @param  array<int|string, mixed>  $items
     * @return list<array{key: int|string, id: ?int, position: int, date: string, kind: string, description: string, amount_cents: int}>
     *
     * @throws DomainException
     */
    private function parseRows(array $items): array
    {
        $rows = [];
        $position = 0;

        foreach ($items as $key => $item) {
            $item = (array) $item;

            if (! filled($item['date'] ?? null) && ! filled($item['description'] ?? null) && ! filled($item['amount'] ?? null) && empty($item['id'])) {
                continue;
            }

            $position++;
            $label = 'Position '.$position;
            $date = null;

            try {
                $date = filled($item['date'] ?? null) ? Carbon::parse((string) $item['date'])->toDateString() : null;
            } catch (Throwable) {
                throw new DomainException($label.': Das Datum ist ungültig.');
            }

            $cents = filled($item['amount'] ?? null) ? Money::parseEuro((string) $item['amount']) : 0;

            if ($cents === null) {
                throw new DomainException($label.': Der Betrag ist ungültig. Bitte in Euro angeben, zum Beispiel 12,50.');
            }

            $kind = (string) ($item['kind'] ?? 'other');

            $rows[] = ['key' => $key, 'id' => filled($item['id'] ?? null) ? (int) $item['id'] : null, 'position' => $position, 'date' => $date ?? now()->toDateString(), 'kind' => array_key_exists($kind, FormItem::KINDS) ? $kind : 'other', 'description' => Str::limit(trim((string) ($item['description'] ?? '')), 200, ''), 'amount_cents' => $cents];
        }

        return $rows;
    }

    // ---------- Einreichen ----------

    /** Letzter Tag, an dem ein Beleg mit diesem Datum noch eingereicht werden kann: Ende Februar des Folgejahres. */
    public function deadlineFor(CarbonInterface $date): Carbon
    {
        return Carbon::create((int) $date->format('Y') + 1, 2, 1)->endOfMonth();
    }

    /** @return list<string> Gründe, warum der Antrag so noch nicht eingereicht werden kann */
    public function problems(FormApplication $application): array
    {
        $application->loadMissing(['items.files']);
        $problems = [];

        if (trim($application->purpose) === '') {
            $problems[] = 'Bitte geben Sie den Zweck an.';
        }

        if ($application->items->isEmpty()) {
            $problems[] = 'Bitte tragen Sie mindestens eine Position ein.';
        }

        foreach ($application->items as $item) {
            $label = 'Position '.$item->position;

            if (trim($item->description) === '') {
                $problems[] = $label.': Bitte beschreiben Sie die Ausgabe.';
            }

            if ($item->amount_cents <= 0) {
                $problems[] = $label.': Der Betrag muss größer als 0 sein.';
            }

            if ($item->date->isFuture()) {
                $problems[] = $label.': Das Datum liegt in der Zukunft.';
            }

            if ($application->exception_by_user_id === null && now()->greaterThan($this->deadlineFor($item->date))) {
                $problems[] = $label.': Belege aus '.$item->date->format('Y').' konnten nur bis '.$this->deadlineFor($item->date)->format('d.m.Y').' eingereicht werden. Bitte wenden Sie sich an die Geschäftsstelle.';
            }

            if ($item->files->isEmpty()) {
                $problems[] = $label.': Es fehlt der Beleg.';
            }
        }

        if (blank($application->account_holder) || blank($application->iban_encrypted) || ! Iban::valid((string) $application->iban_encrypted)) {
            $problems[] = 'Bitte geben Sie Kontoinhaber:in und eine gültige IBAN an.';
        }

        if (empty(((array) $application->data)['declaration'])) {
            $problems[] = 'Bitte bestätigen Sie die Erklärung.';
        }

        return $problems;
    }

    /** @throws DomainException */
    public function submit(FormApplication $application, User $by): FormApplication
    {
        if (! $application->isEditable() || $application->applicant_user_id !== $by->id) {
            throw new DomainException('Dieser Antrag lässt sich nicht einreichen.');
        }

        $problems = $this->problems($application);

        if ($problems !== []) {
            throw new DomainException(implode(' ', $problems));
        }

        $again = $application->status === 'returned';
        $application->forceFill(['status' => 'submitted', 'submitted_at' => now(), 'approval_level' => $this->policy->level($application->total_cents), 'rejection_note' => null, 'checked_at' => null, 'checked_by_user_id' => null, 'self_checked' => false])->save();
        $this->event($application, $again ? 'resubmitted' : 'submitted', $by);
        $this->log($application, $by, 'eingereicht');
        $this->notifyRoles($application, Capability::FormsManage, 'Neuer Antrag wartet auf Prüfung', 'Ein Antrag wartet auf Ihre Prüfung.');

        return $application;
    }

    // ---------- Bearbeitung ----------

    /** @throws DomainException */
    public function startReview(FormApplication $application, User $by): FormApplication
    {
        $this->requireStatus($application, ['submitted']);
        $this->requireCheck($application, $by);

        $application->forceFill(['status' => 'in_review'])->save();
        $this->event($application, 'review', $by);
        $this->log($application, $by, 'in Prüfung');

        return $application;
    }

    /** @throws DomainException */
    public function check(FormApplication $application, User $by): FormApplication
    {
        $this->requireStatus($application, ['submitted', 'in_review']);
        $this->requireCheck($application, $by);

        $self = $this->policy->isSelfCheck($by, $application);
        $application->forceFill(['status' => 'in_review', 'checked_by_user_id' => $by->id, 'checked_at' => now(), 'self_checked' => $self])->save();
        $this->event($application, $self ? 'self_checked' : 'checked', $by);
        $this->log($application, $by, $self ? 'selbst geprüft' : 'geprüft');

        if ($application->approval_level === 'board') {
            $this->notifyRoles($application, Capability::FormsApprove, 'Antrag wartet auf Freigabe', 'Ein Antrag wartet auf Ihre Freigabe.');
        }

        return $application;
    }

    /** @throws DomainException */
    public function approve(FormApplication $application, User $by): FormApplication
    {
        $this->requireStatus($application, ['submitted', 'in_review']);

        if (! $this->policy->canApprove($by, $application)) {
            throw new DomainException($application->applicant_user_id === $by->id
                ? 'Eigene Anträge gibt immer eine andere Person frei.'
                : 'Sie dürfen diesen Antrag nicht freigeben'.($application->approval_level === 'board' ? ' (über '.Money::format($this->settings->approvalLimit()).' gibt Geschäftsführung oder Vorstand frei, nicht die prüfende Person).' : '.'));
        }

        if ($application->checked_at === null) {
            if ($application->approval_level === 'board') {
                throw new DomainException('Der Antrag muss zuerst von der Geschäftsstelle geprüft werden.');
            }

            if ($this->policy->canCheck($by, $application)) {
                $this->check($application, $by);
            }
        }

        $application->forceFill(['status' => 'approved', 'approved_by_user_id' => $by->id, 'approved_at' => now()])->save();
        $this->event($application, 'approved', $by);
        $this->log($application, $by, 'freigegeben');
        $this->notifyApplicant($application, 'Ihr Antrag wurde freigegeben', 'Ihr Antrag wurde freigegeben. Die Auszahlung folgt durch die Geschäftsstelle.');

        return $application;
    }

    /** @throws DomainException */
    public function returnToApplicant(FormApplication $application, User $by, string $note): FormApplication
    {
        $this->requireStatus($application, ['submitted', 'in_review']);
        $this->requireCheck($application, $by);
        $note = $this->requireNote($note, 'Bitte schreiben Sie, was die Antragstellerin oder der Antragsteller klären soll.');

        $application->forceFill(['status' => 'returned', 'rejection_note' => $note, 'checked_at' => null, 'checked_by_user_id' => null])->save();
        $this->event($application, 'returned', $by, $note);
        $this->log($application, $by, 'Rückfrage');
        $this->notifyApplicant($application, 'Rückfrage zu Ihrem Antrag', 'Zu Ihrem Antrag gibt es eine Rückfrage. Bitte melden Sie sich im Portal an und ergänzen Sie die Angaben.');

        return $application;
    }

    /** @throws DomainException */
    public function reject(FormApplication $application, User $by, string $note): FormApplication
    {
        $this->requireStatus($application, ['submitted', 'in_review']);

        if (! $this->policy->canCheck($by, $application) && ! $this->policy->canApprove($by, $application)) {
            throw new DomainException('Sie dürfen diesen Antrag nicht ablehnen.');
        }

        $note = $this->requireNote($note, 'Bitte begründen Sie die Ablehnung.');
        $application->forceFill(['status' => 'rejected', 'rejection_note' => $note, 'retention_until' => $this->retentionEnd()])->save();
        $this->event($application, 'rejected', $by, $note);
        $this->log($application, $by, 'abgelehnt');
        $this->notifyApplicant($application, 'Ihr Antrag wurde abgelehnt', 'Ihr Antrag wurde abgelehnt. Die Begründung steht im Portal.');

        return $application;
    }

    /** @throws DomainException */
    public function markPaid(FormApplication $application, User $by, string $reference): FormApplication
    {
        $this->requireStatus($application, ['approved']);

        if (! Capabilities::has($by, Capability::FormsManage)) {
            throw new DomainException('Nur die Geschäftsstelle vermerkt Auszahlungen.');
        }

        $application->forceFill(['status' => 'paid', 'paid_at' => now(), 'paid_reference' => Str::limit(trim($reference), 100, '') ?: null, 'retention_until' => $this->retentionEnd()])->save();
        $this->event($application, 'paid', $by, trim($reference) ?: null);
        $this->log($application, $by, 'ausgezahlt');
        $this->notifyApplicant($application, 'Ihr Antrag wurde ausgezahlt', 'Der Betrag wurde angewiesen.');

        return $application;
    }

    /** @throws DomainException */
    public function grantException(FormApplication $application, User $by, string $reason): FormApplication
    {
        $this->requireStatus($application, ['draft', 'returned', 'submitted', 'in_review']);

        if (! Capabilities::has($by, Capability::FormsManage)) {
            throw new DomainException('Nur die Geschäftsstelle gibt Ausnahmen bei der Belegfrist frei.');
        }

        $reason = $this->requireNote($reason, 'Bitte begründen Sie die Ausnahme.');
        $application->forceFill(['exception_by_user_id' => $by->id, 'exception_reason' => Str::limit($reason, 300, '')])->save();
        $this->event($application, 'exception', $by, $reason);
        $this->log($application, $by, 'Ausnahme Belegfrist');

        return $application;
    }

    /** Löscht einen Entwurf samt Belegen. @throws DomainException */
    public function discard(FormApplication $application, User $by): void
    {
        if ($application->status !== 'draft' || $application->applicant_user_id !== $by->id) {
            throw new DomainException('Nur eigene Entwürfe lassen sich löschen.');
        }

        $this->files->removeAll($application);
        $application->delete();
    }

    // ---------- Hilfen ----------

    private function requireStatus(FormApplication $application, array $allowed): void
    {
        if (! in_array($application->status, $allowed, true)) {
            throw new DomainException('Das ist im Stand „'.$application->statusLabel().'“ nicht möglich.');
        }
    }

    private function requireCheck(FormApplication $application, User $by): void
    {
        if (! $this->policy->canCheck($by, $application)) {
            throw new DomainException($application->applicant_user_id === $by->id ? 'Eigene Anträge prüft eine andere Person.' : 'Sie dürfen Anträge nicht prüfen.');
        }
    }

    private function requireNote(string $note, string $message): string
    {
        $note = trim($note);

        if ($note === '') {
            throw new DomainException($message);
        }

        return Str::limit($note, 500, '');
    }

    private function retentionEnd(): Carbon
    {
        return Carbon::create((int) now()->format('Y') + 10, 12, 31);
    }

    public function event(FormApplication $application, string $type, ?User $by, ?string $comment = null): void
    {
        FormEvent::query()->create(['request_id' => $application->id, 'type' => $type, 'actor_user_id' => $by?->id, 'actor_label' => $by ? ($by->person?->fullName() ?: (string) $by->email) : null, 'comment' => $comment, 'occurred_at' => now()]);
    }

    private function log(FormApplication $application, User $by, string $what): void
    {
        AuditLog::record(AuditEvent::FormStatusChanged, $by, null, ['nummer' => $application->number, 'art' => $application->typeLabel(), 'aktion' => $what]);
    }

    private function notifyApplicant(FormApplication $application, string $subject, string $text): void
    {
        if (filled($application->applicant_email)) {
            $this->send((string) $application->applicant_email, $application, $subject, $text);
        }
    }

    private function notifyRoles(FormApplication $application, Capability $capability, string $subject, string $text): void
    {
        $roles = Capabilities::roleKeysWith($capability);
        $userIds = RoleAssignment::query()->where('starts_at', '<=', now())->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))->whereHas('role', fn ($query) => $query->whereIn('key', $roles))->pluck('user_id')->unique();

        foreach (User::query()->whereIn('id', $userIds)->where('id', '!=', $application->applicant_user_id ?? 0)->pluck('email') as $email) {
            $this->send((string) $email, $application, $subject, $text);
        }
    }

    private function send(string $email, FormApplication $application, string $subject, string $text): void
    {
        try {
            Mail::to($email)->send(new FormStatusMail($application->number, $application->typeLabel(), $subject, $text));
        } catch (Throwable) {
            // Eine fehlgeschlagene Benachrichtigung darf den Ablauf nicht stoppen.
        }
    }
}
