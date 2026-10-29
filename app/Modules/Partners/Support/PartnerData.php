<?php

namespace App\Modules\Partners\Support;

use App\Modules\Partners\Enums\FederalState;
use App\Modules\Partners\Enums\PartnerStatus;
use App\Modules\Partners\Enums\SchoolType;
use App\Modules\Partners\Models\Partner;
use App\Modules\Partners\Models\PartnerContact;
use Illuminate\Validation\Rule;

/**
 * Eingabefelder einer Schule als Partner. Die **interne Kennung** einer Schule ist Bundesland-Kürzel + Schulnummer, z. B. BE/08K06
 * (Berlin, Schulnummer 08K06). Sie ist eindeutig; die Teile stehen getrennt in attributes (federal_state, school_number).
 *
 * Gemeinsame Regeln und Umwandlung für Formular und CSV-Import. Felder in der Tabelle partners: name, legal_name, Anschrift, Kontakt, status, notes; typische Angaben der Schule
 * (Schulart, Bundesland, Schulnummer, Schülerzahl) stehen in der JSON-Spalte attributes.
 */
final class PartnerData
{
    public const ATTRIBUTES = ['school_type', 'federal_state', 'school_number', 'student_count'];

    /** Schulnummer: Ziffern, Buchstaben und Bindestriche (z. B. 08K06), ohne Schrägstrich und Leerzeichen. */
    public const NUMBER_PATTERN = '/^[0-9A-Za-z][0-9A-Za-z\-]*$/';

    /** Interne Kennung aus Bundesland-Kürzel und Schulnummer, z. B. BE/08K06 (null, wenn eins von beiden fehlt). */
    public static function internalId(?string $state, ?string $number): ?string
    {
        return filled($state) && filled($number) ? strtoupper(trim((string) $state)).'/'.strtoupper(trim((string) $number)) : null;
    }

    /** @return array{state: string, number: string}|null zerlegt BE/08K06 (Bundesland muss bekannt sein) */
    public static function parseInternalId(string $input): ?array
    {
        if (preg_match('#^\s*([A-Za-z]{2})\s*/\s*([0-9A-Za-z][0-9A-Za-z\-]*)\s*$#', $input, $matches) !== 1) {
            return null;
        }

        $state = FederalState::tryFrom(strtoupper($matches[1]));

        return $state !== null ? ['state' => $state->value, 'number' => strtoupper($matches[2])] : null;
    }

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'legal_name' => ['nullable', 'string', 'max:200'],
            'school_type' => ['nullable', Rule::enum(SchoolType::class)],
            'federal_state' => ['nullable', Rule::enum(FederalState::class), 'required_with:school_number'],
            'school_number' => ['nullable', 'string', 'max:20', 'regex:'.self::NUMBER_PATTERN],
            'student_count' => ['nullable', 'integer', 'min:0', 'max:20000'],
            'status' => ['required', Rule::enum(PartnerStatus::class)],
            'street' => ['nullable', 'string', 'max:150'],
            'house_number' => ['nullable', 'string', 'max:30'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:120'],
            'country_code' => ['required', 'string', 'size:2', 'alpha'],
            'email' => ['nullable', 'string', 'email', 'max:254'],
            'phone' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+()\/\-.\s]+$/'],
            'website' => ['nullable', 'string', 'url:http,https', 'max:255'],
            'contacts' => ['nullable', 'array', 'max:'.PartnerContact::MAX_PER_PARTNER],
            'contacts.*.name' => ['nullable', 'string', 'max:150', 'required_with:contacts.*.role,contacts.*.email,contacts.*.phone'],
            'contacts.*.role' => ['nullable', 'string', 'max:100'],
            'contacts.*.email' => ['nullable', 'string', 'email', 'max:254'],
            'contacts.*.phone' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+()\/\-.\s]+$/'],
            'primary_contact' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'name.required' => 'Bitte geben Sie den Namen der Schule ein.',
            'school_type.enum' => 'Bitte wählen Sie eine bekannte Schulart.',
            'federal_state.enum' => 'Bitte wählen Sie ein Bundesland aus der Liste.',
            'federal_state.required_with' => 'Zur Schulnummer gehört das Bundesland (interne Kennung, z. B. BE/08K06).',
            'school_number.regex' => 'Die Schulnummer darf nur Ziffern, Buchstaben und Bindestriche enthalten, z. B. 08K06.',
            'student_count.integer' => 'Die Schülerzahl muss eine ganze Zahl sein.',
            'student_count.min' => 'Die Schülerzahl darf nicht negativ sein.',
            'student_count.max' => 'Bitte prüfen Sie die Schülerzahl.',
            'status.required' => 'Bitte wählen Sie den Status.',
            'status.enum' => 'Bitte wählen Sie einen gültigen Status.',
            'country_code.required' => 'Bitte geben Sie das Land als zweistelligen Code an, z. B. DE.',
            'country_code.size' => 'Bitte geben Sie das Land als zweistelligen Code an, z. B. DE.',
            'country_code.alpha' => 'Bitte geben Sie das Land als zweistelligen Code an, z. B. DE.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse der Schule ein.',
            'phone.regex' => 'Bitte geben Sie eine gültige Telefonnummer der Schule ein.',
            'website.url' => 'Bitte geben Sie die Webseite mit https:// an, z. B. https://www.beispiel.example.',
            'contacts.max' => 'Es sind höchstens '.PartnerContact::MAX_PER_PARTNER.' Ansprechpersonen je Schule möglich.',
            'contacts.*.name.required_with' => 'Bitte geben Sie den Namen der Ansprechperson ein.',
            'contacts.*.email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'contacts.*.phone.regex' => 'Bitte geben Sie eine gültige Telefonnummer ein.',
        ];
    }

    /**
     * Formulardaten in Spalten von partners umwandeln (leere Werte werden null, die Schulangaben kommen nach attributes).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function columns(array $data): array
    {
        $clean = static fn (mixed $value): mixed => is_string($value) ? (trim($value) === '' ? null : trim($value)) : $value;

        $columns = [];

        foreach (['name', 'legal_name', 'street', 'house_number', 'postal_code', 'city', 'email', 'phone', 'website', 'notes'] as $field) {
            $columns[$field] = $clean($data[$field] ?? null);
        }

        $columns['email'] = $columns['email'] !== null ? mb_strtolower($columns['email']) : null;
        $columns['country_code'] = strtoupper((string) ($clean($data['country_code'] ?? null) ?? 'DE'));
        $columns['status'] = (string) ($data['status'] ?? PartnerStatus::Active->value);

        $attributes = [];

        foreach (self::ATTRIBUTES as $field) {
            $value = $clean($data[$field] ?? null);

            if ($value !== null) {
                $attributes[$field] = $field === 'student_count' ? (int) $value : $value;
            }
        }

        if (isset($attributes['school_number'])) {
            $attributes['school_number'] = strtoupper((string) $attributes['school_number']);
        }

        $columns['attributes'] = $attributes === [] ? null : $attributes;

        return $columns;
    }

    /**
     * Ansprechpersonen aus den Formulardaten: leere Zeilen entfallen, E-Mail klein; Hauptansprechperson ist die gewählte Zeile, sonst die erste.
     *
     * @param  array<string, mixed>  $data
     * @return list<array{name: string, role: ?string, email: ?string, phone: ?string, is_primary: bool}>
     */
    public static function contacts(array $data): array
    {
        $clean = static fn (mixed $value): ?string => is_string($value) && trim($value) !== '' ? trim($value) : null;

        $rows = [];
        $primaryKey = null;
        $chosen = isset($data['primary_contact']) && is_numeric($data['primary_contact']) ? (string) (int) $data['primary_contact'] : null;

        // Die Zeilen sind über ihren Formularindex benannt (nach dem Entfernen von Zeilen mit Lücken)
        foreach ((array) ($data['contacts'] ?? []) as $index => $row) {
            $name = $clean($row['name'] ?? null);

            if ($name === null) {
                continue;
            }

            $rows[] = [
                'name' => $name,
                'role' => $clean($row['role'] ?? null),
                'email' => ($email = $clean($row['email'] ?? null)) !== null ? mb_strtolower($email) : null,
                'phone' => $clean($row['phone'] ?? null),
                'is_primary' => false,
            ];

            if ((string) $index === $chosen) {
                $primaryKey = array_key_last($rows);
            }
        }

        if ($rows !== []) {
            $rows[$primaryKey ?? 0]['is_primary'] = true;
        }

        return $rows;
    }

    /** @return list<array{name: ?string, role: ?string, email: ?string, phone: ?string}> die vorhandenen Ansprechpersonen (Hauptansprechperson zuerst) */
    public static function contactRows(Partner $partner): array
    {
        $rows = $partner->contacts->map(fn (PartnerContact $contact): array => ['name' => $contact->name, 'role' => $contact->role, 'email' => $contact->email, 'phone' => $contact->phone])->all();

        return $rows;
    }

    /** @return array<string, mixed> Werte fürs Formular */
    public static function fromPartner(Partner $partner): array
    {
        $attributes = $partner->getAttribute('attributes') ?? [];

        return [
            'name' => $partner->name,
            'legal_name' => $partner->legal_name,
            'school_type' => $attributes['school_type'] ?? null,
            'federal_state' => $attributes['federal_state'] ?? null,
            'school_number' => $attributes['school_number'] ?? null,
            'student_count' => $attributes['student_count'] ?? null,
            'status' => $partner->status->value,
            'street' => $partner->street,
            'house_number' => $partner->house_number,
            'postal_code' => $partner->postal_code,
            'city' => $partner->city,
            'country_code' => $partner->country_code,
            'email' => $partner->email,
            'phone' => $partner->phone,
            'website' => $partner->website,
            'contacts' => self::contactRows($partner),
            'primary_contact' => 0,
            'notes' => $partner->notes,
        ];
    }
}
