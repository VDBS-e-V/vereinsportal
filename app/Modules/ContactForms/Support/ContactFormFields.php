<?php

namespace App\Modules\ContactForms\Support;

use App\Modules\ContactForms\Models\ContactForm;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Eigene Felder eines Kontaktformulars: erlaubte Arten, Aufbereitung der Eingabe aus der Verwaltung, Prüfregeln und Aufbereitung für die E-Mail. */
final class ContactFormFields
{
    public const TYPES = [
        'text' => 'Kurzer Text',
        'textarea' => 'Langer Text',
        'email' => 'E-Mail-Adresse',
        'tel' => 'Telefonnummer',
        'select' => 'Auswahl aus einer Liste',
        'checkbox' => 'Ja/Nein (Kästchen)',
        'date' => 'Datum',
    ];

    public const MAX_FIELDS = 20;

    /**
     * Aus den Zeilen des Verwaltungsformulars die gespeicherte Feldliste machen: leere Zeilen entfallen, Arten und Schlüssel werden geprüft.
     *
     * @param  array<int|string, mixed>  $rows
     * @return list<array{key: string, label: string, type: string, required: bool, options: list<string>, hint: ?string}>
     */
    public static function normalize(array $rows): array
    {
        $fields = [];
        $used = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $label = trim((string) ($row['label'] ?? ''));

            if ($label === '' || count($fields) >= self::MAX_FIELDS) {
                continue;
            }

            $type = array_key_exists((string) ($row['type'] ?? ''), self::TYPES) ? (string) $row['type'] : 'text';
            $key = (string) ($row['key'] ?? '');

            if (! preg_match('/^[a-z0-9_]{1,40}$/', $key)) {
                $key = Str::limit(Str::slug($label, '_'), 40, '');
                $key = $key !== '' ? $key : 'feld';
            }

            $base = $key;

            for ($n = 2; in_array($key, $used, true); $n++) {
                $key = Str::limit($base, 36, '').'_'.$n;
            }

            $used[] = $key;
            $options = [];

            if ($type === 'select') {
                $lines = is_array($row['options'] ?? null) ? $row['options'] : preg_split('/\R/u', (string) ($row['options'] ?? ''));
                $options = array_slice(array_values(array_unique(array_filter(array_map(fn ($line): string => Str::limit(trim((string) $line), 100, ''), (array) $lines)))), 0, 30);
            }

            $fields[] = [
                'key' => $key,
                'label' => Str::limit($label, 120, ''),
                'type' => $type,
                'required' => filter_var($row['required'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'options' => $options,
                'hint' => filled($row['hint'] ?? null) ? Str::limit(trim((string) $row['hint']), 200, '') : null,
            ];
        }

        return $fields;
    }

    /**
     * Prüfregeln für die Antworten (answers.<key>).
     *
     * @return array{0: array<string, mixed>, 1: array<string, string>, 2: array<string, string>}
     */
    public static function rules(ContactForm $form): array
    {
        $rules = [];
        $messages = [];
        $attributes = [];

        foreach ($form->customFields() as $field) {
            $name = 'answers.'.$field['key'];
            $required = (bool) $field['required'];

            $rule = match ($field['type']) {
                'textarea' => ['string', 'max:5000'],
                'email' => ['email', 'max:254'],
                'tel' => ['string', 'max:50', 'regex:/^[0-9+()\/ .\-]{3,50}$/'],
                'select' => [Rule::in($field['options'])],
                'checkbox' => [$required ? 'accepted' : 'boolean'],
                'date' => ['date'],
                default => ['string', 'max:500'],
            };

            $rules[$name] = [$required ? 'required' : 'nullable', ...$rule];
            $attributes[$name] = $field['label'];
            $messages[$name.'.required'] = 'Bitte füllen Sie „'.$field['label'].'“ aus.';
            $messages[$name.'.accepted'] = 'Bitte bestätigen Sie „'.$field['label'].'“.';
            $messages[$name.'.in'] = 'Bitte wählen Sie bei „'.$field['label'].'“ einen Eintrag aus der Liste.';
            $messages[$name.'.email'] = 'Bitte geben Sie bei „'.$field['label'].'“ eine gültige E-Mail-Adresse ein.';
            $messages[$name.'.regex'] = 'Bitte geben Sie bei „'.$field['label'].'“ eine gültige Telefonnummer ein.';
            $messages[$name.'.date'] = 'Bitte geben Sie bei „'.$field['label'].'“ ein gültiges Datum ein.';
        }

        return [$rules, $messages, $attributes];
    }

    /**
     * Die Antworten als Liste für die E-Mail: Bezeichnung und Text.
     *
     * @param  array<string, mixed>  $answers
     * @return list<array{label: string, value: string}>
     */
    public static function lines(ContactForm $form, array $answers): array
    {
        $lines = [];

        foreach ($form->customFields() as $field) {
            $value = $answers[$field['key']] ?? null;

            $text = match ($field['type']) {
                'checkbox' => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'Ja' : 'Nein',
                default => filled($value) ? trim((string) $value) : '–',
            };

            $lines[] = ['label' => $field['label'], 'value' => $text];
        }

        return $lines;
    }
}
