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

    public function create(User $user, string $type, ?string $audience = null): FormApplication
    {
        $audience ??= FormTypes::audienceForRoles(Capabilities::activeRoleKeys($user));

        if (! in_array($type, FormTypes::typesFor($audience), true)) {
            throw new DomainException('Diese Art von Antrag gibt es hier nicht.');
        }

        $user->loadMissing('person');

        $application = FormApplication::query()->create([
            'number' => $this->numbers->next($type),
            'type' => $type,
            'applicant_user_id' => $user->id,
            'applicant_name' => $user->person?->fullName() ?: (string) $user->email,
            'applicant_email' => $user->email,
            'status' => 'draft',
            'data' => ['audience' => $audience],
        ]);

        $this->event($application, 'created', $user);

        return $application;
    }

    // ---------- Entwurf ----------

    /**
     * Speichert den Entwurf. Unvollständige Angaben sind erlaubt; falsche Formate nicht.
     *
     * @param  array<string, mixed>  $input  purpose, seminar_id, cost_center, items[<n>][...], account_holder, iban, declaration (Bestellung: urgency, wanted_date, delivery, delivery_address, delivery_contact)
     * @param  array<int|string, UploadedFile>  $uploads  Beleg je Positionsnummer
     *
     * @throws DomainException
     */
    public function saveDraft(FormApplication $application, array $input, array $uploads = []): FormApplication
    {
        if (! $application->isEditable()) {
            throw new DomainException('Dieser Antrag lässt sich nicht mehr ändern.');
        }

        $config = FormTypes::audience((string) (((array) $application->data)['audience'] ?? 'employee'));
        $seminarId = $config['seminar'] && filled($input['seminar_id'] ?? null) ? (int) $input['seminar_id'] : null;

        if ($seminarId !== null && ! Seminar::query()->whereKey($seminarId)->exists()) {
            throw new DomainException('Das gewählte Seminar gibt es nicht.');
        }

        $costCenter = $config['cost_center'] && filled($input['cost_center'] ?? null) ? (string) $input['cost_center'] : null;

        if ($costCenter !== null && ! CostCenter::query()->where('code', $costCenter)->where('active', true)->exists()) {
            throw new DomainException('Die gewählte Kostenstelle gibt es nicht.');
        }

        $iban = trim((string) ($input['iban'] ?? ''));

        if ($iban !== '' && ! Iban::valid($iban)) {
            throw new DomainException('Die IBAN ist nicht gültig. Bitte prüfen Sie Länderkennung und Ziffern.');
        }

        $rows = $this->parseRows($application->type, (array) ($input['items'] ?? []));

        DB::transaction(function () use ($application, $input, $seminarId, $costCenter, $iban, $rows, $uploads): void {
            $keep = [];

            foreach ($rows as $row) {
                $item = isset($row['id']) ? $application->items()->whereKey($row['id'])->first() : null;
                $data = ['position' => $row['position'], 'date' => $row['date'], 'kind' => $row['kind'], 'description' => $row['description'], 'quantity' => $row['quantity'], 'unit_cents' => $row['unit_cents'], 'amount_cents' => $row['amount_cents'], 'extra' => $row['extra']];

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

            if ($application->type === 'purchase') {
                $urgency = (string) ($input['urgency'] ?? 'normal');
                $data['urgency'] = in_array($urgency, ['normal', 'urgent'], true) ? $urgency : 'normal';
                $data['wanted_date'] = filled($input['wanted_date'] ?? null) ? $this->safeDate((string) $input['wanted_date']) : null;
                $data['delivery'] = ($input['delivery'] ?? 'office') === 'other' ? 'other' : 'office';
                $data['delivery_address'] = Str::limit(trim((string) ($input['delivery_address'] ?? '')), 300, '');
                $data['delivery_contact'] = Str::limit(trim((string) ($input['delivery_contact'] ?? '')), 150, '');
            }

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

    private function safeDate(string $value): ?string
    {
        try {
            return Carbon::parse($value)->toDateString();
        } catch (Throwable) {
            throw new DomainException('Das Datum ist ungültig.');
        }
    }

    /**
     * @param  array<int|string, mixed>  $items
     * @return list<array{key: int|string, id: ?int, position: int, date: string, kind: string, description: string, quantity: float, unit_cents: int, amount_cents: int, extra: ?array<string, mixed>}>
     *
     * @throws DomainException
     */
    private function parseRows(string $type, array $items): array
    {
        $rows = [];
        $position = 0;

        foreach ($items as $key => $item) {
            $item = (array) $item;
            $blank = collect($item)->except(['id', 'kind', 'mode', 'round_trip'])->filter(fn ($value): bool => filled($value))->isEmpty();

            if ($blank && empty($item['id'])) {
                continue;
            }

            $position++;
            $label = 'Position '.$position;
            $id = filled($item['id'] ?? null) ? (int) $item['id'] : null;

            $rows[] = match ($type) {
                'travel' => $this->travelRow($key, $id, $position, $label, $item),
                'purchase' => $this->purchaseRow($key, $id, $position, $label, $item),
                default => $this->expenseRow($key, $id, $position, $label, $item),
            };
        }

        return $rows;
    }

    private function dateOf(string $label, mixed $value, bool $fallbackToday = true): string
    {
        if (! filled($value)) {
            return $fallbackToday ? now()->toDateString() : '';
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (Throwable) {
            throw new DomainException($label.': Das Datum ist ungültig.');
        }
    }

    private function euroOf(string $label, mixed $value): int
    {
        if (! filled($value)) {
            return 0;
        }

        $cents = Money::parseEuro((string) $value);

        if ($cents === null) {
            throw new DomainException($label.': Der Betrag ist ungültig. Bitte in Euro angeben, zum Beispiel 12,50.');
        }

        return $cents;
    }

    private function numberOf(string $label, mixed $value, string $what): float
    {
        if (! filled($value)) {
            return 0.0;
        }

        $text = str_replace(',', '.', trim((string) $value));

        if (preg_match('/^\d{1,6}(\.\d{1,2})?$/', $text) !== 1) {
            throw new DomainException($label.': '.$what.' ist ungültig.');
        }

        return (float) $text;
    }

    /** @return array<string, mixed> */
    private function expenseRow(int|string $key, ?int $id, int $position, string $label, array $item): array
    {
        $kind = (string) ($item['kind'] ?? 'other');
        $cents = $this->euroOf($label, $item['amount'] ?? null);

        return ['key' => $key, 'id' => $id, 'position' => $position, 'date' => $this->dateOf($label, $item['date'] ?? null), 'kind' => array_key_exists($kind, FormItem::KINDS) ? $kind : 'other', 'description' => Str::limit(trim((string) ($item['description'] ?? '')), 200, ''), 'quantity' => 1.0, 'unit_cents' => $cents, 'amount_cents' => $cents, 'extra' => null];
    }

    /** Fahrt: PKW rechnet Kilometer mal Satz (Hin- und Rückfahrt verdoppelt), sonst zählt der Fahrschein; Parkgebühr kommt dazu. @return array<string, mixed> */
    private function travelRow(int|string $key, ?int $id, int $position, string $label, array $item): array
    {
        $mode = in_array(($item['mode'] ?? 'car'), ['car', 'train', 'public', 'ride'], true) ? (string) $item['mode'] : 'car';
        $round = ! empty($item['round_trip']);
        $km = $this->numberOf($label, $item['km'] ?? null, 'Die Kilometerangabe');
        $ticket = $this->euroOf($label, $item['ticket'] ?? null);
        $parking = $this->euroOf($label, $item['parking'] ?? null);
        $rate = $this->settings->int('km_rate_cents');
        $distance = $km * ($round ? 2 : 1);
        $fare = $mode === 'car' ? (int) round($distance * $rate) : $ticket;
        $from = Str::limit(trim((string) ($item['from'] ?? '')), 100, '');
        $to = Str::limit(trim((string) ($item['to'] ?? '')), 100, '');
        $modeLabels = ['car' => 'PKW', 'train' => 'Bahn', 'public' => 'Nahverkehr', 'ride' => 'Mitfahrt'];

        return [
            'key' => $key, 'id' => $id, 'position' => $position, 'date' => $this->dateOf($label, $item['date'] ?? null), 'kind' => 'travel',
            'description' => Str::limit(trim($from.' → '.$to.' ('.$modeLabels[$mode].($round ? ', hin und zurück' : '').')'), 200, ''),
            'quantity' => $mode === 'car' ? $distance : 1.0, 'unit_cents' => $mode === 'car' ? $rate : $ticket, 'amount_cents' => $fare + $parking,
            'extra' => ['from' => $from, 'to' => $to, 'mode' => $mode, 'round_trip' => $round, 'km' => $km, 'ticket_cents' => $mode === 'car' ? 0 : $ticket, 'parking_cents' => $parking, 'rate_cents' => $rate],
        ];
    }

    /** Bestellposition: Menge mal Einzelpreis (brutto). @return array<string, mixed> */
    private function purchaseRow(int|string $key, ?int $id, int $position, string $label, array $item): array
    {
        $quantity = filled($item['quantity'] ?? null) ? $this->numberOf($label, $item['quantity'], 'Die Menge') : 1.0;
        $unit = $this->euroOf($label, $item['unit'] ?? null);

        return [
            'key' => $key, 'id' => $id, 'position' => $position, 'date' => now()->toDateString(), 'kind' => 'material',
            'description' => Str::limit(trim((string) ($item['description'] ?? '')), 200, ''), 'quantity' => $quantity, 'unit_cents' => $unit, 'amount_cents' => (int) round($quantity * $unit),
            'extra' => ['supplier' => Str::limit(trim((string) ($item['supplier'] ?? '')), 150, ''), 'link' => Str::limit(trim((string) ($item['link'] ?? '')), 300, '')],
        ];
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
        $type = $application->type;

        if (trim($application->purpose) === '') {
            $problems[] = 'Bitte geben Sie den Zweck an.';
        }

        if ($application->items->isEmpty()) {
            $problems[] = 'Bitte tragen Sie mindestens eine Position ein.';
        }

        foreach ($application->items as $item) {
            $label = 'Position '.$item->position;
            $extra = (array) $item->extra;

            if ($item->amount_cents <= 0) {
                $problems[] = $label.': Der Betrag muss größer als 0 sein.';
            }

            if ($type === 'purchase') {
                if (trim($item->description) === '') {
                    $problems[] = $label.': Bitte benennen Sie den Artikel.';
                }

                if ($item->quantity <= 0) {
                    $problems[] = $label.': Die Menge muss größer als 0 sein.';
                }

                continue;
            }

            if ($type === 'travel') {
                if (blank($extra['from'] ?? null) || blank($extra['to'] ?? null)) {
                    $problems[] = $label.': Bitte geben Sie Start und Ziel an.';
                }

                if (($extra['mode'] ?? 'car') === 'car' && (float) ($extra['km'] ?? 0) <= 0) {
                    $problems[] = $label.': Bitte geben Sie die Kilometer an.';
                }

                if (($extra['mode'] ?? 'car') !== 'car' && (int) ($extra['ticket_cents'] ?? 0) <= 0) {
                    $problems[] = $label.': Bitte geben Sie den Fahrpreis an.';
                }

                $needsReceipt = (int) ($extra['ticket_cents'] ?? 0) > 0 || (int) ($extra['parking_cents'] ?? 0) > 0;
            } else {
                if (trim($item->description) === '') {
                    $problems[] = $label.': Bitte beschreiben Sie die Ausgabe.';
                }

                $needsReceipt = true;
            }

            if ($item->date->isFuture()) {
                $problems[] = $label.': Das Datum liegt in der Zukunft.';
            }

            if ($application->exception_by_user_id === null && now()->greaterThan($this->deadlineFor($item->date))) {
                $problems[] = $label.': Belege aus '.$item->date->format('Y').' konnten nur bis '.$this->deadlineFor($item->date)->format('d.m.Y').' eingereicht werden. Bitte wenden Sie sich an die Geschäftsstelle.';
            }

            if ($needsReceipt && $item->files->isEmpty()) {
                $problems[] = $label.': Es fehlt der Beleg.';
            }
        }

        if ($type === 'purchase') {
            $data = (array) $application->data;

            if (($data['delivery'] ?? 'office') === 'other' && blank($data['delivery_address'] ?? null)) {
                $problems[] = 'Bitte geben Sie die Lieferadresse an.';
            }
        } elseif (blank($application->account_holder) || blank($application->iban_encrypted) || ! Iban::valid((string) $application->iban_encrypted)) {
            $problems[] = 'Bitte geben Sie Kontoinhaber:in und eine gültige IBAN an.';
        }

        if (empty(((array) $application->data)['declaration'])) {
            $problems[] = 'Bitte bestätigen Sie die Erklärung.';
        }

        return $problems;
    }

    /** @return list<string> Hinweise, die das Einreichen nicht verhindern (zum Beispiel sehr lange Fahrten) */
    public function warnings(FormApplication $application): array
    {
        $max = $this->settings->int('max_km_per_trip');
        $warnings = [];

        foreach ($application->items as $item) {
            $km = (float) (((array) $item->extra)['km'] ?? 0) * (! empty(((array) $item->extra)['round_trip']) ? 2 : 1);

            if ($application->type === 'travel' && $max > 0 && $km > $max) {
                $warnings[] = 'Position '.$item->position.': '.rtrim(rtrim(number_format($km, 2, ',', ''), '0'), ',').' km sind sehr viel. Bitte prüfen Sie die Angabe (kürzeste übliche Strecke).';
            }
        }

        return $warnings;
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
        $this->notifyApplicant($application, 'Ihr Antrag wurde freigegeben', $application->type === 'purchase' ? 'Ihre Bestellung wurde freigegeben. Die Geschäftsstelle bestellt jetzt.' : 'Ihr Antrag wurde freigegeben. Die Auszahlung folgt durch die Geschäftsstelle.');

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

        if ($application->type === 'purchase') {
            throw new DomainException('Bestellungen werden nicht ausgezahlt. Bitte vermerken Sie Bestellung, Lieferung und Rechnung.');
        }

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

    // ---------- Bestellung: bestellt, geliefert, Rechnung, Abschluss ----------

    /** @throws DomainException */
    public function markOrdered(FormApplication $application, User $by): FormApplication
    {
        $this->requirePurchaseStep($application, $by, ['approved'], 'ordered', 'bestellt', 'Die Bestellung ist aufgegeben.');

        return $application;
    }

    /** @throws DomainException */
    public function markDelivered(FormApplication $application, User $by): FormApplication
    {
        $this->requirePurchaseStep($application, $by, ['ordered'], 'delivered', 'geliefert', 'Die Lieferung ist angekommen.');

        return $application;
    }

    /** Hängt die Rechnung an eine Bestellung. @throws DomainException */
    public function attachInvoice(FormApplication $application, User $by, UploadedFile $file): FormApplication
    {
        if ($application->type !== 'purchase' || ! in_array($application->status, ['ordered', 'delivered'], true) || ! Capabilities::has($by, Capability::FormsManage)) {
            throw new DomainException('Die Rechnung lässt sich hier nicht anhängen.');
        }

        $stored = $this->files->add($application, $file);
        $data = (array) $application->data;
        $data['invoice_file_id'] = $stored->id;
        $application->forceFill(['data' => $data])->save();
        $this->event($application, 'invoice', $by);

        return $application;
    }

    /** @throws DomainException */
    public function close(FormApplication $application, User $by): FormApplication
    {
        if ($application->type === 'purchase' && empty(((array) $application->data)['invoice_file_id'])) {
            throw new DomainException('Bitte hängen Sie zuerst die Rechnung an.');
        }

        $this->requirePurchaseStep($application, $by, ['delivered'], 'closed', 'abgeschlossen', 'Der Antrag ist abgeschlossen.', true);
        $application->forceFill(['retention_until' => $this->retentionEnd()])->save();

        return $application;
    }

    /** @param  list<string>  $from */
    private function requirePurchaseStep(FormApplication $application, User $by, array $from, string $to, string $what, string $mailText, bool $silent = false): void
    {
        if ($application->type !== 'purchase') {
            throw new DomainException('Das gibt es nur bei Bestellungen.');
        }

        if (! Capabilities::has($by, Capability::FormsManage)) {
            throw new DomainException('Nur die Geschäftsstelle trägt das ein.');
        }

        $this->requireStatus($application, $from);
        $application->forceFill(['status' => $to])->save();
        $this->event($application, $to, $by);
        $this->log($application, $by, $what);

        if (! $silent) {
            $this->notifyApplicant($application, 'Zu Ihrer Bestellung gibt es Neuigkeiten', $mailText);
        }
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
