<?php

namespace App\Modules\Partners\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Partners\Enums\FederalState;
use App\Modules\Partners\Enums\PartnerStatus;
use App\Modules\Partners\Enums\SchoolType;
use App\Modules\Partners\Models\Partner;
use App\Modules\Partners\Models\PartnerContact;
use App\Modules\Partners\Support\PartnerData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * CSV-Import von Schulen. Alles oder nichts: Enthält die Datei fehlerhafte Zeilen, wird nichts angelegt und jede Zeile mit
 * Fehler gemeldet. Schulen, die es mit gleichem Namen am selben Ort schon gibt, werden übersprungen (nicht überschrieben).
 * Die interne Kennung (Bundesland-Kürzel + Schulnummer, z. B. BE/08K06) muss eindeutig sein. Ansprechpersonen stehen in Spaltengruppen
 * (Ansprechperson, Funktion, E-Mail, Telefon; weitere mit Zusatz „2“, „3“ usw.). Erkannt werden Semikolon und Komma als Trennzeichen,
 * UTF-8 (mit oder ohne BOM) und Windows-1252.
 */
final class PartnerImporter
{
    public const MAX_ROWS = 1000;

    /** Spaltenüberschrift (klein) => internes Feld */
    private const HEADERS = [
        'name' => 'name', 'schule' => 'name', 'name der schule' => 'name',
        'träger' => 'legal_name', 'offizieller name' => 'legal_name',
        'interne kennung' => 'internal_id', 'kennung' => 'internal_id', 'schulkennung' => 'internal_id',
        'schulart' => 'school_type',
        'bundesland' => 'federal_state',
        'schulnummer' => 'school_number',
        'straße' => 'street', 'strasse' => 'street',
        'hausnummer' => 'house_number',
        'plz' => 'postal_code', 'postleitzahl' => 'postal_code',
        'ort' => 'city',
        'e-mail der schule' => 'email', 'telefon der schule' => 'phone', 'webseite' => 'website', 'website' => 'website',
        'schülerzahl' => 'student_count', 'schüler:innen' => 'student_count', 'schueler' => 'student_count',
        'status' => 'status',
        'notiz' => 'notes', 'notizen' => 'notes',
    ];

    /** Teile einer Ansprechperson: Überschrift => Feld */
    private const CONTACT_PARTS = [
        'ansprechperson' => 'name', 'kontakt' => 'name',
        'funktion' => 'role',
        'e-mail' => 'email', 'email' => 'email',
        'telefon' => 'phone',
    ];

    public function __construct(private readonly PartnerAdministration $partners) {}

    /** Kopfzeile mit der angegebenen Zahl von Ansprechpersonen-Gruppen. @return list<string> */
    public static function header(int $contactGroups = 2): array
    {
        $header = ['Name', 'Träger', 'Interne Kennung', 'Schulart', 'Bundesland', 'Schulnummer', 'Straße', 'Hausnummer', 'PLZ', 'Ort', 'E-Mail der Schule', 'Telefon der Schule', 'Webseite'];

        foreach (range(1, max(1, min(PartnerContact::MAX_PER_PARTNER, $contactGroups))) as $group) {
            $suffix = $group === 1 ? '' : ' '.$group;
            array_push($header, 'Ansprechperson'.$suffix, 'Funktion'.$suffix, 'E-Mail'.$suffix, 'Telefon'.$suffix);
        }

        return array_merge($header, ['Schülerzahl', 'Status', 'Notiz']);
    }

    /** Vorlage für den Download: Kopfzeile und eine Beispielzeile. @return list<list<string>> */
    public static function template(): array
    {
        return [
            self::header(2),
            ['Beispielschule', 'Land Berlin', 'BE/08K06', 'Gymnasium', 'Berlin', '08K06', 'Schulstraße', '1', '10115', 'Berlin', 'sekretariat@beispiel.example', '030 654321', 'https://www.beispiel.example',
                'Frau Beispiel', 'Schulleitung', 'leitung@beispiel.example', '030 123456',
                'Herr Muster', 'Bibliothek', 'bibliothek@beispiel.example', '',
                '650', 'aktiv', ''],
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
        $seenNames = [];
        $seenIds = [];
        $skipped = 0;

        foreach ($rows['rows'] as $line => $raw) {
            $row = $this->normalize($raw);

            if (isset($row['_error'])) {
                $errors[] = "Zeile {$line}: {$row['_error']}";

                continue;
            }

            $validator = Validator::make($row, PartnerData::rules(), PartnerData::messages());

            if ($validator->fails()) {
                $errors[] = "Zeile {$line}: ".implode(' ', $validator->errors()->all());

                continue;
            }

            $nameKey = mb_strtolower(trim($row['name'])).'|'.mb_strtolower(trim((string) ($row['city'] ?? '')));

            if (isset($seenNames[$nameKey])) {
                $errors[] = "Zeile {$line}: „{$row['name']}“ kommt in der Datei schon in Zeile {$seenNames[$nameKey]} vor.";

                continue;
            }

            $seenNames[$nameKey] = $line;

            $internalId = PartnerData::internalId($row['federal_state'] ?? null, $row['school_number'] ?? null);

            if ($internalId !== null && isset($seenIds[$internalId])) {
                $errors[] = "Zeile {$line}: Die interne Kennung {$internalId} kommt in der Datei schon in Zeile {$seenIds[$internalId]} vor.";

                continue;
            }

            if ($this->partners->exists($row['name'], $row['city'] ?? null)) {
                $skipped++;

                continue;
            }

            if ($internalId !== null && $this->partners->internalIdExists($row['federal_state'], $row['school_number'])) {
                $errors[] = "Zeile {$line}: Die interne Kennung {$internalId} gehört schon zu einer anderen Schule.";

                continue;
            }

            if ($internalId !== null) {
                $seenIds[$internalId] = $line;
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
            $field = $this->fieldFor(mb_strtolower(trim((string) $title)));

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

    /** Interne Feldbezeichnung einer Überschrift, auch „contact{n}_{teil}“ für Ansprechpersonen („Funktion 2“ usw.). */
    private function fieldFor(string $title): ?string
    {
        if (isset(self::HEADERS[$title])) {
            return self::HEADERS[$title];
        }

        if (preg_match('/^(.+?)(?:\s+([1-9][0-9]{0,2}))?$/u', $title, $matches) === 1 && isset(self::CONTACT_PARTS[$matches[1]]) && (int) ($matches[2] ?? 1) <= PartnerContact::MAX_PER_PARTNER) {
            return 'contact'.($matches[2] ?? '1').'_'.self::CONTACT_PARTS[$matches[1]];
        }

        return null;
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

        if (($row['internal_id'] ?? '') !== '') {
            $parsed = PartnerData::parseInternalId($row['internal_id']);

            if ($parsed === null) {
                return $row + ['_error' => "Die interne Kennung „{$row['internal_id']}“ ist ungültig. Erwartet wird Bundesland-Kürzel und Schulnummer, z. B. BE/08K06."];
            }

            if (($row['federal_state'] ?? '') !== '' && strtoupper($row['federal_state']) !== $parsed['state']
                || ($row['school_number'] ?? '') !== '' && strtoupper($row['school_number']) !== $parsed['number']) {
                return $row + ['_error' => 'Die interne Kennung '.$parsed['state'].'/'.$parsed['number'].' passt nicht zu den Spalten Bundesland und Schulnummer.'];
            }

            $row['federal_state'] = $parsed['state'];
            $row['school_number'] = $parsed['number'];
        }

        $row['status'] = PartnerStatus::fromInput($row['status'] ?? '')?->value ?? $row['status'];

        $row['country_code'] = 'DE';

        $contacts = [];

        foreach (range(1, PartnerContact::MAX_PER_PARTNER) as $group) {
            $contact = [];

            foreach (['name', 'role', 'email', 'phone'] as $part) {
                $contact[$part] = $row["contact{$group}_{$part}"] ?? '';
                unset($row["contact{$group}_{$part}"]);
            }

            if (array_filter($contact, fn (string $value): bool => $value !== '') !== []) {
                $contacts[] = $contact;
            }
        }

        $row['contacts'] = $contacts;
        $row['primary_contact'] = 0;
        unset($row['internal_id']);

        return $row;
    }
}
