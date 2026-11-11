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
                'absender_telefon' => 'Telefonnummer der absendenden Person (falls im Profil)',
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
                'empfaenger_telefon' => 'Telefonnummer (falls bekannt)',
            ]],
            'school' => ['label' => 'Schule', 'items' => [
                'schule_name' => 'Name der Schule',
                'schule_anschrift' => 'Anschrift der Schule in mehreren Zeilen',
                'ansprechperson' => 'Name der Hauptansprechperson der Schule',
                'ansprechperson_email' => 'E-Mail-Adresse der Hauptansprechperson',
                'ansprechperson_telefon' => 'Telefonnummer der Hauptansprechperson',
                'schule_telefon' => 'Telefonnummer der Schule',
                'schule_email' => 'E-Mail-Adresse der Schule',
                'schule_nummer' => 'Interne Kennung der Schule, z. B. BE/08K06',
                'schule_bundesland' => 'Bundesland der Schule',
                'schule_schulart' => 'Schulart',
            ]],
            'condition' => ['label' => 'Bedingungen (für Wenn-Blöcke, siehe unten)', 'items' => [
                'empfaenger_ist_schule' => 'Ist gefüllt, wenn das Schreiben an eine Schule geht',
                'empfaenger_ist_person' => 'Ist gefüllt, wenn das Schreiben an eine Person aus dem Team geht',
            ]],
            'seminar' => ['label' => 'Seminar', 'items' => [
                'seminar_titel' => 'Titel des Seminars',
                'seminar_nr' => 'Seminarnummer, z. B. S-2027-0042',
                'seminar_art' => 'Art des Seminars',
                'seminar_termine' => 'Alle Termine, jeder in einer eigenen Zeile',
                'seminar_erster_termin' => 'Erster Termin',
                'seminar_ort' => 'Ort des ersten Termins',
                'seminar_teilnehmende' => 'Erwartete Zahl der Teilnehmenden',
                'seminar_beginn' => 'Datum des ersten Termins, z. B. 12.05.2027',
                'seminar_ende' => 'Datum des letzten Termins',
                'seminar_tage' => 'Zahl der Tage mit Terminen',
                'seminar_stunden' => 'Gesamtdauer aller Termine in Stunden',
                'seminar_team' => 'Namen der bestätigten Teamenden, durch Komma getrennt',
                'seminar_koordination' => 'Verantwortliche Koordination',
                'seminar_frist' => 'Stichtag für Interessenbekundungen',
                'verguetung' => 'Honorar je Person laut Kostenvoranschlag (ohne Seminarleitung), z. B. 150,00 €',
            ]],
        ];
    }

    /** Wenn-Blöcke: ${wenn_NAME} … ${/wenn_NAME} zeigt den Inhalt nur, wenn NAME einen Wert hat; ${ohne_NAME} … ${/ohne_NAME} nur, wenn NAME leer ist. */
    public const BLOCK_PREFIXES = ['wenn_', 'ohne_'];

    /** Name ohne Block-Schreibweise: „/wenn_schule_name“ wird zu „schule_name“, andere Namen bleiben. */
    public static function baseName(string $name): string
    {
        $name = ltrim($name, '/');

        foreach (self::BLOCK_PREFIXES as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return substr($name, strlen($prefix));
            }
        }

        return $name;
    }

    /** @return list<string> */
    public static function known(): array
    {
        return array_values(array_merge(...array_map(fn (array $group): array => array_keys($group['items']), self::groups())));
    }
}
