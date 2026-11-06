<?php

namespace App\Modules\Contracts\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Contracts\Models\ContractorProfile;
use App\Modules\Contracts\Models\SeminarContract;
use App\Modules\Forms\Services\Iban;
use App\Modules\Seminars\Enums\ApplicationStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use App\Modules\Seminars\Models\SeminarDocument;
use App\Modules\Seminars\Services\SeminarDocuments;
use App\Modules\Seminars\Support\Money;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Dienstleistungsverträge für bestätigte Teamende eines Seminars. Das Portal erzeugt sie nur zum Ausdrucken; unterschrieben wird auf Papier,
 * der Scan wird als Seminar-Dokument (Art Vertrag) abgelegt. Fehlende Werte bleiben leer und werden von Hand ergänzt.
 */
final class Contracts
{
    public function __construct(private readonly ContractTexts $texts, private readonly ContractSettings $settings, private readonly SeminarDocuments $documents) {}

    /** Bestätigte Bewerbungen eines Seminars mit dem jeweils gültigen Vertrag (oder null). @return list<array{application: SeminarApplication, contract: ?SeminarContract}> */
    public function overview(Seminar $seminar): array
    {
        $contracts = SeminarContract::query()->where('seminar_id', $seminar->id)->where('status', '!=', 'cancelled')->get()->keyBy('application_id');

        return $seminar->applications()->where('status', ApplicationStatus::Confirmed->value)->with(['user.person', 'contact'])->orderBy('id')->get()
            ->map(fn (SeminarApplication $application): array => ['application' => $application, 'contract' => $contracts->get($application->id)])->all();
    }

    /** Vorbelegte Daten für einen Vertrag. @return array<string, mixed> */
    public function defaults(Seminar $seminar, SeminarApplication $application): array
    {
        $seminar->loadMissing(['partner', 'sessions']);
        $user = $application->user;
        $profile = $user ? ContractorProfile::query()->where('user_id', $user->id)->first() : null;
        $person = $user?->person;
        $contact = $application->contact;

        $parts = $person
            ? [$person->street.' '.$person->house_number, trim($person->postal_code.' '.$person->city)]
            : [trim((string) ($contact?->street).' '.(string) ($contact?->house_number)), trim((string) ($contact?->postal_code).' '.(string) ($contact?->city))];
        $address = collect($parts)->map(fn ($part) => trim((string) $part))->filter()->implode(', ');

        $sessions = $seminar->sessions->sortBy('starts_at')->values();
        $hours = round($sessions->sum(fn ($session): float => max(0, $session->starts_at->diffInMinutes($session->ends_at)) / 60), 1);
        $dates = $sessions->map(fn ($session): string => $session->period())->implode('; ');

        $service = trim('Durchführung des Seminars '.$seminar->type->label().' „'.$seminar->title.'“'
            .($seminar->partner?->name ? ' an der Schule '.$seminar->partner->name : '')
            .($dates !== '' ? ', Termine: '.$dates : '')
            .($hours > 0 ? ', insgesamt '.str_replace('.', ',', (string) $hours).' Stunden' : '').'.');

        $amount = $this->honorarium($seminar);

        return [
            'name' => $person ? trim($person->last_name.', '.$person->first_name) : trim(((string) $contact?->last_name).', '.((string) $contact?->first_name), ', '),
            'address' => $address,
            'tax_number' => $profile?->tax_number,
            'vat_mode' => $profile?->vat_mode,
            'bank_name' => $profile?->bank_name,
            'bic' => $profile?->bic,
            'iban' => $profile?->iban,
            'service_text' => $service,
            'amount_cents' => $amount,
            'amount_original_cents' => $amount,
            'leader_hint_cents' => $this->leaderRate($seminar),
            'amount_note' => null,
            'place' => $this->settings->get('place'),
            'date' => now()->format('d.m.Y'),
        ];
    }

    /** Honorar je Person aus dem neuesten Kostenvoranschlag (Honorarzeile geteilt durch die Zahl der Teamenden). */
    public function honorarium(Seminar $seminar): int
    {
        $quote = $seminar->quotes()->orderByDesc('id')->first();
        $sum = 0;

        foreach ((array) ($quote?->positions ?? []) as $position) {
            foreach ((array) ($position['lines'] ?? []) as $line) {
                if (($line['key'] ?? null) === 'honorarium') {
                    $sum += (int) round((int) $line['total_cents'] / max(1, (float) $line['quantity']));
                }
            }
        }

        return $sum;
    }

    /** Satz für die Seminarleitung je Person aus dem neuesten Kostenvoranschlag (nur als Hinweis; der Zuschlag wird von Hand in die Vergütung eingerechnet). */
    public function leaderRate(Seminar $seminar): int
    {
        $quote = $seminar->quotes()->orderByDesc('id')->first();
        $sum = 0;

        foreach ((array) ($quote?->positions ?? []) as $position) {
            foreach ((array) ($position['lines'] ?? []) as $line) {
                if (($line['key'] ?? null) === 'leader') {
                    $sum += (int) round((int) $line['total_cents'] / max(1, (float) $line['quantity']));
                }
            }
        }

        return $sum;
    }

    /**
     * Erzeugt Verträge für bestätigte Personen (alle oder die gewählten Bewerbungen). Personen mit gültigem Vertrag werden übersprungen.
     *
     * @param  list<int>|null  $applicationIds
     */
    public function createMany(Seminar $seminar, ?array $applicationIds, User $by): int
    {
        $count = 0;

        foreach ($this->overview($seminar) as $row) {
            if ($row['contract'] !== null || ($applicationIds !== null && ! in_array($row['application']->id, $applicationIds, true))) {
                continue;
            }

            $this->create($seminar, $row['application'], $by);
            $count++;
        }

        if ($count > 0) {
            AuditLog::record(AuditEvent::ContractsCreated, $by, $seminar, ['nummer' => (string) $seminar->number, 'anzahl' => $count]);
        }

        return $count;
    }

    public function create(Seminar $seminar, SeminarApplication $application, User $by): SeminarContract
    {
        if ($application->seminar_id !== $seminar->id || $application->status !== ApplicationStatus::Confirmed) {
            throw new DomainException('Verträge gibt es nur für bestätigte Personen dieses Seminars.');
        }

        if (SeminarContract::query()->where('application_id', $application->id)->where('status', '!=', 'cancelled')->exists()) {
            throw new DomainException('Für diese Person gibt es schon einen Vertrag.');
        }

        $template = $this->texts->active();

        return SeminarContract::query()->create([
            'number' => $this->nextNumber(),
            'seminar_id' => $seminar->id,
            'application_id' => $application->id,
            'user_id' => $application->user_id,
            'template_id' => $template->id,
            'template_version' => $template->version,
            'data' => $this->defaults($seminar, $application),
            'status' => 'created',
            'created_by_user_id' => $by->id,
            'retention_until' => $this->documents->retentionFor($seminar, 'contract'),
        ]);
    }

    /**
     * Ändert die Angaben eines noch nicht unterschriebenen Vertrags. Weicht die Vergütung vom Kostenvoranschlag ab, ist ein Vermerk nötig.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws DomainException
     */
    public function update(SeminarContract $contract, array $input, User $by): SeminarContract
    {
        if (! $contract->isEditable()) {
            throw new DomainException('Dieser Vertrag lässt sich nicht mehr ändern.');
        }

        $data = (array) $contract->data;
        $amount = filled($input['amount'] ?? null) ? Money::parseEuro((string) $input['amount']) : 0;

        if ($amount === null) {
            throw new DomainException('Bitte geben Sie die Vergütung als Betrag in Euro an, zum Beispiel 75,00.');
        }

        $note = trim((string) ($input['amount_note'] ?? ''));

        if ($amount !== (int) ($data['amount_original_cents'] ?? 0) && $note === '') {
            throw new DomainException('Bitte nennen Sie einen Vermerk, warum die Vergütung vom Kostenvoranschlag abweicht.');
        }

        $iban = trim((string) ($input['iban'] ?? ''));

        if ($iban !== '' && ! Iban::valid($iban)) {
            throw new DomainException('Die IBAN ist nicht gültig.');
        }

        $vat = $input['vat_mode'] ?? null;
        $trim = fn (string $key): ?string => filled($input[$key] ?? null) ? trim((string) $input[$key]) : null;

        $contract->forceFill(['data' => array_merge($data, [
            'name' => $trim('name'), 'address' => $trim('address'), 'tax_number' => $trim('tax_number'),
            'vat_mode' => in_array($vat, ['19', '7', 'none'], true) ? $vat : null,
            'bank_name' => $trim('bank_name'), 'bic' => $trim('bic'), 'iban' => $iban !== '' ? Iban::pretty($iban) : null,
            'service_text' => $trim('service_text'), 'amount_cents' => $amount, 'amount_note' => $note !== '' ? $note : null,
            'place' => $trim('place'), 'date' => $trim('date'),
        ])])->save();

        // Auf Wunsch die Vertragsdaten für künftige Verträge im Teamkonto merken
        if (! empty($input['remember']) && $contract->user_id !== null) {
            app(ContractorProfiles::class)->save($contract->user, [
                'tax_number' => $trim('tax_number'), 'vat_mode' => $contract->data['vat_mode'] ?? null, 'bank_name' => $trim('bank_name'), 'bic' => $trim('bic'),
                'iban' => $iban, 'account_holder' => $trim('name'),
            ]);
        }

        return $contract;
    }

    public function cancel(SeminarContract $contract): void
    {
        if ($contract->status === 'signed') {
            throw new DomainException('Ein unterschriebener Vertrag lässt sich nicht stornieren.');
        }

        $contract->forceFill(['status' => 'cancelled'])->save();
    }

    /**
     * Legt den unterschriebenen Scan als Seminar-Dokument (Art Vertrag) ab und setzt den Vertrag auf „unterschrieben“.
     *
     * @throws DomainException
     */
    public function attachScan(SeminarContract $contract, UploadedFile $file, User $by): SeminarContract
    {
        if ($contract->status === 'cancelled') {
            throw new DomainException('Der Vertrag ist storniert.');
        }

        $document = $this->documents->add($contract->seminar, $file, 'contract', 'Vertrag '.$contract->number, $by);

        $contract->forceFill(['status' => 'signed', 'signed_document_id' => $document->id, 'signed_at' => now()])->save();
        AuditLog::record(AuditEvent::ContractSigned, $by, $contract->seminar, ['nummer' => (string) $contract->seminar->number]);

        return $contract;
    }

    /** Sind alle bestätigten Personen mit unterschriebenem Scan versorgt? (Null: es gibt noch keine Verträge, der Punkt entfällt.) */
    public function allSigned(Seminar $seminar): ?bool
    {
        $contracts = SeminarContract::query()->where('seminar_id', $seminar->id)->where('status', '!=', 'cancelled')->get();

        if ($contracts->isEmpty()) {
            return null;
        }

        $confirmed = $seminar->applications()->where('status', ApplicationStatus::Confirmed->value)->pluck('id');

        return $confirmed->diff($contracts->where('status', 'signed')->pluck('application_id'))->isEmpty();
    }

    private function nextNumber(): string
    {
        $scope = 'K-'.now()->format('Y');

        $number = DB::transaction(function () use ($scope): int {
            DB::table('contract_counters')->insertOrIgnore(['scope' => $scope, 'last' => 0]);
            $last = (int) DB::table('contract_counters')->where('scope', $scope)->lockForUpdate()->value('last');
            DB::table('contract_counters')->where('scope', $scope)->update(['last' => $last + 1]);

            return $last + 1;
        });

        return $scope.'-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }
}
