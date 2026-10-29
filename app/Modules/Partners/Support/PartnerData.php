<?php

namespace App\Modules\Partners\Support;

use App\Modules\Partners\Enums\FederalState;
use App\Modules\Partners\Enums\PartnerStatus;
use App\Modules\Partners\Enums\SchoolType;
use App\Modules\Partners\Models\Partner;
use Illuminate\Validation\Rule;

/**
 * Eingabefelder einer Schule als Partner: gemeinsame Regeln und Umwandlung für Formular und CSV-Import.
 * Felder in der Tabelle partners: name, legal_name, Anschrift, Kontakt, status, notes; typische Angaben der Schule
 * (Schulart, Bundesland, Schulnummer, Schülerzahl) stehen in der JSON-Spalte attributes.
 */
final class PartnerData
{
    public const ATTRIBUTES = ['school_type', 'federal_state', 'school_number', 'student_count'];

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'legal_name' => ['nullable', 'string', 'max:200'],
            'school_type' => ['nullable', Rule::enum(SchoolType::class)],
            'federal_state' => ['nullable', Rule::enum(FederalState::class)],
            'school_number' => ['nullable', 'string', 'max:30'],
            'student_count' => ['nullable', 'integer', 'min:0', 'max:20000'],
            'status' => ['required', Rule::enum(PartnerStatus::class)],
            'street' => ['nullable', 'string', 'max:150'],
            'house_number' => ['nullable', 'string', 'max:30'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:120'],
            'country_code' => ['required', 'string', 'size:2', 'alpha'],
            'contact_name' => ['nullable', 'string', 'max:150'],
            'contact_email' => ['nullable', 'string', 'email', 'max:254'],
            'contact_phone' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+()\/\-.\s]+$/'],
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
            'student_count.integer' => 'Die Schülerzahl muss eine ganze Zahl sein.',
            'student_count.min' => 'Die Schülerzahl darf nicht negativ sein.',
            'student_count.max' => 'Bitte prüfen Sie die Schülerzahl.',
            'status.required' => 'Bitte wählen Sie den Status.',
            'status.enum' => 'Bitte wählen Sie einen gültigen Status.',
            'country_code.required' => 'Bitte geben Sie das Land als zweistelligen Code an, z. B. DE.',
            'country_code.size' => 'Bitte geben Sie das Land als zweistelligen Code an, z. B. DE.',
            'country_code.alpha' => 'Bitte geben Sie das Land als zweistelligen Code an, z. B. DE.',
            'contact_email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'contact_phone.regex' => 'Bitte geben Sie eine gültige Telefonnummer ein.',
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

        foreach (['name', 'legal_name', 'street', 'house_number', 'postal_code', 'city', 'contact_name', 'contact_email', 'contact_phone', 'notes'] as $field) {
            $columns[$field] = $clean($data[$field] ?? null);
        }

        $columns['contact_email'] = $columns['contact_email'] !== null ? mb_strtolower((string) $columns['contact_email']) : null;
        $columns['country_code'] = strtoupper((string) ($clean($data['country_code'] ?? null) ?? 'DE'));
        $columns['status'] = (string) ($data['status'] ?? PartnerStatus::Active->value);

        $attributes = [];

        foreach (self::ATTRIBUTES as $field) {
            $value = $clean($data[$field] ?? null);

            if ($value !== null) {
                $attributes[$field] = $field === 'student_count' ? (int) $value : $value;
            }
        }

        $columns['attributes'] = $attributes === [] ? null : $attributes;

        return $columns;
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
            'contact_name' => $partner->contact_name,
            'contact_email' => $partner->contact_email,
            'contact_phone' => $partner->contact_phone,
            'notes' => $partner->notes,
        ];
    }
}
