<?php

namespace App\Modules\Partners\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Partners\Enums\FederalState;
use App\Modules\Partners\Enums\PartnerStatus;
use App\Modules\Partners\Enums\SchoolType;
use App\Modules\Partners\Support\PartnerData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * CSV-Import von Schulen. Alles oder nichts: Enthält die Datei fehlerhafte Zeilen, wird nichts angelegt und jede Zeile mit
 * Fehler gemeldet. Schulen, die es mit gleichem Namen am selben Ort schon gibt, werden übersprungen (nicht überschrieben).
 * Erkannt werden Semikolon und Komma als Trennzeichen, UTF-8 (mit oder ohne BOM) und Windows-1252.
 */
final class PartnerImporter
{
    public const MAX_ROWS = 1000;

    /** Spaltenüberschrift (klein, ohne Leerzeichen am Rand) => internes Feld */
    private const HEADERS = [
        'name' => 'name', 'schule' => 'name', 'name der schule' => 'name',
        'träger' => 'legal_name', 'offizieller name' => 'legal_name',
        'schulart' => 'school_type',
        'bundesland' => 'federal_state',
        'schulnummer' => 'school_number',
        'straße' => 'street', 'strasse' => 'street',
        'hausnummer' => 'house_number',
        'plz' => 'postal_code', 'postleitzahl' => 'postal_code',
        'ort' => 'city',
        'ansprechperson' => 'contact_name', 'kontakt' => 'contact_name',
        'e-mail' => 'contact_email', 'email' => 'contact_email', 'e-mail-adresse' => 'contact_email',
        'telefon' => 'contact_phone',
        'schülerzahl' => 'student_count', 'schüler:innen' => 'student_count', 'schueler' => 'student_count',
        'status' => 'status',
        'notiz' => 'notes', 'notizen' => 'notes',
    ];

    public function __construct(private readonly PartnerAdministration $partners) {}

    /** Vorlage für den Download: Kopfzeile und eine Beispielzeile. @return list<list<string>> */
    public static function template(): array
    {
        return [
            ['Name', 'Träger', 'Schulart', 'Bundesland', 'Schulnummer', 'Straße', 'Hausnummer', 'PLZ', 'Ort', 'Ansprechperson', 'E-Mail', 'Telefon', 'Schülerzahl', 'Status', 'Notiz'],
            ['Beispielschule', '', 'Gymnasium', 'Sachsen', '12345', 'Schulstraße', '1', '04109', 'Leipzig', 'Frau Beispiel', 'sekretariat@beispiel.example', '0341 123456', '650', 'aktiv', ''],
        ];
    }

    /**
     * @return array{created: int, skipped: int, errors: list<string>}
     */
    public function import(string $contents, User $by): array
    {
        $rows = $this->parse($contents);

        if (isset($rows['error'])) {
            return ['created' => 0, 'skipped' => 0, 'errors' => [$rows['error']]];
        }

        $errors = [];
        $valid = [];
        $seen = [];
        $skipped = 0;

        foreach ($rows['rows'] as $line => $row) {
            $row = $this->normalize($row);
            $validator = Validator::make($row, PartnerData::rules(), PartnerData::messages());

            if ($validator->fails()) {
                $errors[] = "Zeile {$line}: ".implode(' ', $validator->errors()->all());

                continue;
            }

            $key = mb_strtolower(trim($row['name'])).'|'.mb_strtolower(trim((string) ($row['city'] ?? '')));

            if (isset($seen[$key])) {
                $errors[] = "Zeile {$line}: „{$row['name']}“ kommt in der Datei schon in Zeile {$seen[$key]} vor.";

                continue;
            }

            $seen[$key] = $line;

            if ($this->partners->exists($row['name'], $row['city'] ?? null)) {
                $skipped++;

                continue;
            }

            $valid[] = $row;
        }

        if ($errors !== []) {
            return ['created' => 0, 'skipped' => 0, 'errors' => $errors];
        }

        DB::transaction(function () use ($valid, $by): void {
            foreach ($valid as $row) {
                $this->partners->create($row, $by);
            }
        });

        AuditLog::record(AuditEvent::PartnersImported, $by, null, ['angelegt' => count($valid), 'uebersprungen' => $skipped]);

        return ['created' => count($valid), 'skipped' => $skipped, 'errors' => []];
    }

    /**
     * @return array{rows: array<int, array<string, string>>}|array{error: string}
     */
    private function parse(string $contents): array
    {
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;

        if (! mb_check_encoding($contents, 'UTF-8')) {
            $contents = mb_convert_encoding($contents, 'UTF-8', 'Windows-1252');
        }

        $firstLine = strtok($contents, "\n") ?: '';
        $delimiter = substr_count($firstLine, ';') >= substr_count($firstLine, ',') ? ';' : ',';

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $contents);
        rewind($handle);

        $header = fgetcsv($handle, 0, $delimiter, '"', '\\');

        if ($header === false || $header === [null]) {
            return ['error' => 'Die Datei ist leer.'];
        }

        $fields = [];
        $unknown = [];

        foreach ($header as $index => $title) {
            $key = mb_strtolower(trim((string) $title));
            $field = self::HEADERS[$key] ?? null;

            if ($field === null) {
                $unknown[] = trim((string) $title);

                continue;
            }

            $fields[$index] = $field;
        }

        if ($unknown !== []) {
            return ['error' => 'Unbekannte Spalten: '.implode(', ', array_filter($unknown, fn (string $title): bool => $title !== '')).'. Laden Sie die Vorlage herunter, um die richtigen Überschriften zu sehen.'];
        }

        if (! in_array('name', $fields, true)) {
            return ['error' => 'Die Spalte „Name“ fehlt.'];
        }

        $rows = [];
        $line = 1;

        while (($record = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            $line++;

            if ($record === [null] || count(array_filter($record, fn ($cell): bool => trim((string) $cell) !== '')) === 0) {
                continue;
            }

            if (count($rows) >= self::MAX_ROWS) {
                return ['error' => 'Die Datei enthält mehr als '.self::MAX_ROWS.' Zeilen. Bitte teilen Sie sie auf.'];
            }

            $row = [];

            foreach ($fields as $index => $field) {
                $row[$field] = trim((string) ($record[$index] ?? ''));
            }

            $rows[$line] = $row;
        }

        fclose($handle);

        return $rows === [] ? ['error' => 'Die Datei enthält keine Schulen.'] : ['rows' => $rows];
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, mixed>
     */
    private function normalize(array $row): array
    {
        if (($row['school_type'] ?? '') !== '') {
            $row['school_type'] = SchoolType::fromInput($row['school_type'])?->value ?? $row['school_type'];
        }

        if (($row['federal_state'] ?? '') !== '') {
            $row['federal_state'] = FederalState::fromInput($row['federal_state'])?->value ?? $row['federal_state'];
        }

        $status = mb_strtolower($row['status'] ?? '');
        $row['status'] = match (true) {
            $status === '' || in_array($status, ['aktiv', 'active'], true) => PartnerStatus::Active->value,
            in_array($status, ['inaktiv', 'inactive'], true) => PartnerStatus::Inactive->value,
            default => $row['status'],
        };

        $row['country_code'] = 'DE';

        return $row;
    }
}
