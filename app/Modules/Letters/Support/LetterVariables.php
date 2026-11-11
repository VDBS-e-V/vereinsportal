<?php

namespace App\Modules\Letters\Support;

/** Die Platzhalter, die das Portal in Word-Vorlagen füllt. In Word schreiben Sie sie so: ${datum}. Gruppiert nach Herkunft. */
final class LetterVariables
{
    /** @return array<string, array{label: string, items: array<string, string>}> */
    public static function groups(): array
    {
        return [
            'general' => ['label' => 'Allgemein', 'items' => [
                'datum' => 'Heutiges Datum, z. B. 11.11.2026',
                'datum_lang' => 'Heutiges Datum ausgeschrieben, z. B. 11. November 2026',
                'jahr' => 'Aktuelles Jahr',
                'betreff' => 'Betreff (beim Erzeugen eingegeben)',
                'freitext' => 'Freier Text (beim Erzeugen eingegeben, mehrere Zeilen möglich)',
            ]],
            'sender' => ['label' => 'Absender', 'items' => [
                'absender_name' => 'Name der Person, die das Schreiben erzeugt',
                'absender_email' => 'E-Mail-Adresse dieser Person',
                'verein_name' => 'Name des Vereins (ausgeschrieben)',
                'verein_kurz' => 'Kurzname des Vereins',
                'verein_vorsitz' => 'Vorsitz',
                'verein_anschrift' => 'Anschrift des Vereins',
                'verein_ort' => 'Ort des Vereins',
            ]],
            'recipient' => ['label' => 'Empfänger (Person oder Schule)', 'items' => [
                'empfaenger_name' => 'Vollständiger Name (Person) oder Name der Schule',
                'empfaenger_vorname' => 'Vorname (nur Personen)',
                'empfaenger_nachname' => 'Nachname (nur Personen)',
                'empfaenger_anschrift' => 'Anschrift in mehreren Zeilen (Straße, PLZ Ort)',
                'empfaenger_strasse' => 'Straße und Hausnummer',
                'empfaenger_plz' => 'Postleitzahl',
                'empfaenger_ort' => 'Ort',
                'empfaenger_email' => 'E-Mail-Adresse',
            ]],
            'school' => ['label' => 'Schule', 'items' => [
                'schule_name' => 'Name der Schule',
                'schule_anschrift' => 'Anschrift der Schule in mehreren Zeilen',
                'ansprechperson' => 'Name der Hauptansprechperson der Schule',
            ]],
            'seminar' => ['label' => 'Seminar', 'items' => [
                'seminar_titel' => 'Titel des Seminars',
                'seminar_nr' => 'Seminarnummer, z. B. S-2027-0042',
                'seminar_art' => 'Art des Seminars',
                'seminar_termine' => 'Alle Termine, jeder in einer eigenen Zeile',
                'seminar_erster_termin' => 'Erster Termin',
                'seminar_ort' => 'Ort des ersten Termins',
                'seminar_teilnehmende' => 'Erwartete Zahl der Teilnehmenden',
            ]],
        ];
    }

    /** @return list<string> */
    public static function known(): array
    {
        return array_values(array_merge(...array_map(fn (array $group): array => array_keys($group['items']), self::groups())));
    }
}
