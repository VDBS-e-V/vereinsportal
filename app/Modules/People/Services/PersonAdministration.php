<?php

namespace App\Modules\People\Services;

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Services\UserAdministration;
use App\Modules\People\Models\Person;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use DomainException;

/** Verwaltungsaktionen an Personen: anlegen, ändern, löschen und ein Konto aus einer Person erstellen. */
final class PersonAdministration
{
    /** Felder, die Verwaltung und Person selbst ändern dürfen (die E-Mail-Adresse nur ohne Konto). */
    public const FIELDS = ['title', 'first_name', 'name_addition', 'last_name', 'birth_date', 'phone', 'street', 'house_number', 'postal_code', 'city', 'country_code'];

    public function __construct(private readonly UserAdministration $users) {}

    /** @param  array<string, mixed>  $data */
    public function create(array $data, User $by): Person
    {
        $person = Person::query()->create($this->normalize($data) + ['email' => mb_strtolower(trim((string) $data['email']))]);

        AuditLog::record(AuditEvent::PersonCreated, $by, $person);

        return $person;
    }

    /** @param  array<string, mixed>  $data */
    public function update(Person $person, array $data, User $by): Person
    {
        $attributes = $this->normalize($data);

        // Mit Konto ist die E-Mail-Adresse die Anmeldung und wird nur über „Konto > E-Mail“ geändert.
        if (! $person->user()->exists() && isset($data['email'])) {
            $attributes['email'] = mb_strtolower(trim((string) $data['email']));
        }

        $person->fill($attributes)->save();

        $changed = array_values(array_diff(array_keys($person->getChanges()), ['updated_at']));

        if ($changed !== []) {
            AuditLog::record(AuditEvent::PersonUpdated, $by, $person, ['felder' => $changed]);
        }

        return $person;
    }

    public function delete(Person $person, User $by): void
    {
        if ($person->user()->exists()) {
            throw new DomainException('Personen mit Benutzerkonto können nicht gelöscht werden. Sperren Sie stattdessen das Konto.');
        }

        if ($person->memberships()->exists()) {
            throw new DomainException('Personen mit Mitgliedschaft können nicht gelöscht werden.');
        }

        $person->delete();

        AuditLog::record(AuditEvent::PersonDeleted, $by, $person);
    }

    public function createAccount(Person $person, User $by): User
    {
        return $this->users->createForPerson($person, $by);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        $attributes = [];

        foreach (self::FIELDS as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $value = is_string($data[$field]) ? trim($data[$field]) : $data[$field];
            $attributes[$field] = $value === '' ? null : $value;
        }

        $attributes['country_code'] = strtoupper((string) ($attributes['country_code'] ?? 'DE'));

        return $attributes;
    }
}
