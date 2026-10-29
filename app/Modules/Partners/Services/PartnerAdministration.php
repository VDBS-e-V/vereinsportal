<?php

namespace App\Modules\Partners\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Partners\Enums\PartnerType;
use App\Modules\Partners\Models\Partner;
use App\Modules\Partners\Models\PartnerContact;
use App\Modules\Partners\Support\PartnerData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Schulen als Partner anlegen, ändern und löschen (jeweils mit Eintrag im Protokoll). */
final class PartnerAdministration
{
    /** @param  array<string, mixed>  $data  validierte Formulardaten (PartnerData::rules) */
    public function create(array $data, User $by): Partner
    {
        $partner = DB::transaction(function () use ($data, $by): Partner {
            $partner = Partner::query()->create(PartnerData::columns($data) + [
                'public_id' => (string) Str::ulid(),
                'type' => PartnerType::School,
                'created_by_user_id' => $by->id,
                'updated_by_user_id' => $by->id,
            ]);

            $this->syncContacts($partner, PartnerData::contacts($data));

            return $partner;
        });

        AuditLog::record(AuditEvent::PartnerCreated, $by, $partner);

        return $partner;
    }

    /** @param  array<string, mixed>  $data */
    public function update(Partner $partner, array $data, User $by): Partner
    {
        $contactsChanged = false;

        DB::transaction(function () use ($partner, $data, $by, &$contactsChanged): void {
            $partner->fill(PartnerData::columns($data) + ['updated_by_user_id' => $by->id])->save();

            $contactsChanged = $this->syncContacts($partner, PartnerData::contacts($data));
        });

        $changed = array_values(array_diff(array_keys($partner->getChanges()), ['updated_at', 'updated_by_user_id']));

        if ($contactsChanged) {
            $changed[] = 'ansprechpersonen';
        }

        if ($changed !== []) {
            AuditLog::record(AuditEvent::PartnerUpdated, $by, $partner, ['felder' => $changed]);
        }

        return $partner;
    }

    public function delete(Partner $partner, User $by): void
    {
        AuditLog::record(AuditEvent::PartnerDeleted, $by, $partner);

        $partner->delete();
    }

    /**
     * Ersetzt die Ansprechpersonen durch die übergebenen (höchstens MAX_PER_PARTNER). Verknüpfungen zu Personen bleiben erhalten,
     * wenn dieselbe Ansprechperson (Name und E-Mail) weiterhin vorkommt.
     *
     * @param  list<array{name: string, role: ?string, email: ?string, phone: ?string, is_primary: bool}>  $contacts
     * @return bool ob sich etwas geändert hat
     */
    public function syncContacts(Partner $partner, array $contacts): bool
    {
        $contacts = array_slice($contacts, 0, PartnerContact::MAX_PER_PARTNER);
        $existing = $partner->contacts()->get();

        $signature = fn (array $rows): array => array_map(fn (array $row): string => json_encode([$row['name'], $row['role'], $row['email'], $row['phone'], $row['is_primary']], JSON_UNESCAPED_UNICODE), $rows);
        $current = $signature($existing->map(fn (PartnerContact $contact): array => ['name' => $contact->name, 'role' => $contact->role, 'email' => $contact->email, 'phone' => $contact->phone, 'is_primary' => $contact->is_primary])->all());

        if ($current === $signature($contacts)) {
            return false;
        }

        $people = $existing->filter(fn (PartnerContact $contact): bool => $contact->person_id !== null)->mapWithKeys(fn (PartnerContact $contact): array => [mb_strtolower($contact->name.'|'.$contact->email) => $contact->person_id]);

        $partner->contacts()->delete();

        foreach ($contacts as $contact) {
            $partner->contacts()->create($contact + ['person_id' => $people[mb_strtolower($contact['name'].'|'.$contact['email'])] ?? null]);
        }

        $partner->unsetRelation('contacts');

        return true;
    }

    /** Gibt es schon eine andere Schule mit dieser internen Kennung (Bundesland + Schulnummer)? */
    public function internalIdExists(?string $state, ?string $number, ?int $exceptId = null): bool
    {
        if (! filled($state) || ! filled($number)) {
            return false;
        }

        return Partner::query()
            ->where('type', PartnerType::School->value)
            ->where('attributes->federal_state', strtoupper(trim($state)))
            ->where('attributes->school_number', strtoupper(trim($number)))
            ->when($exceptId !== null, fn ($query) => $query->where('id', '!=', $exceptId))
            ->exists();
    }

    /** Gibt es schon eine Schule mit diesem Namen am selben Ort (ohne Beachtung der Groß-/Kleinschreibung)? */
    public function exists(string $name, ?string $city, ?int $exceptId = null): bool
    {
        return Partner::query()
            ->where('type', PartnerType::School->value)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])
            ->where(fn ($query) => filled($city)
                ? $query->whereRaw('LOWER(city) = ?', [mb_strtolower(trim((string) $city))])
                : $query->whereNull('city'))
            ->when($exceptId !== null, fn ($query) => $query->where('id', '!=', $exceptId))
            ->exists();
    }
}
