<?php

namespace App\Modules\Letters\Support;

/** Die Platzhalter, die das Portal in Word-Vorlagen füllt. In Word schreiben Sie sie so: ${datum}. Gruppiert nach Herkunft. */
final class LetterVariables
{
    /** Eintrag einer Platzhalter-Beschreibung. @return array{meaning: string, example: string, source: string, note: string} */
    private static function v(string $meaning, string $example, string $source, string $note = ''): array
    {
        return ['meaning' => $meaning, 'example' => $example, 'source' => $source, 'note' => $note];
    }

    /**
     * Alle Platzhalter in Gruppen, jeweils mit Bedeutung, Beispielwert, Herkunft der Angabe und Hinweis.
     *
     * @return array<string, array{label: string, intro: string, items: array<string, array{meaning: string, example: string, source: string, note: string}>}>
     */
    public static function groups(): array
    {
        return [
            'general' => ['label' => 'Allgemein', 'intro' => 'Gelten für jedes Schreiben, unabhängig vom Empfänger.', 'items' => [
                'datum' => self::v('Heutiges Datum', '11.11.2026', 'Datum beim Erzeugen'),
                'datum_lang' => self::v('Heutiges Datum ausgeschrieben', '11. November 2026', 'Datum beim Erzeugen'),
                'jahr' => self::v('Aktuelles Jahr', '2026', 'Datum beim Erzeugen'),
                'betreff' => self::v('Betreff des Schreibens', 'Bestätigung Ihres Seminars', 'Feld „Betreff“ beim Erzeugen', 'Leer, wenn beim Erzeugen nichts eingetragen wird. Mit ${wenn_betreff} lässt sich eine Betreffzeile ausblenden.'),
                'freitext' => self::v('Freier Text, mehrere Zeilen möglich', 'Wir freuen uns auf Sie.', 'Feld „Freier Text“ beim Erzeugen', 'Zeilenumbrüche bleiben erhalten. Steht am besten allein in einem Absatz.'),
            ]],
            'sender' => ['label' => 'Absender', 'intro' => 'Die Person, die das Schreiben im Portal erzeugt, und der Verein.', 'items' => [
                'absender_name' => self::v('Name der erzeugenden Person', 'Karla Koordina', 'Konto der angemeldeten Person (Stammdaten)'),
                'absender_email' => self::v('E-Mail-Adresse dieser Person', 'karla@vdb.schule', 'Konto der angemeldeten Person'),
                'absender_telefon' => self::v('Telefonnummer dieser Person', '030 123456', 'Stammdaten der Person', 'Leer, wenn keine Nummer gespeichert ist.'),
                'verein_name' => self::v('Name des Vereins, ausgeschrieben', 'Verband für Demokratiebildung und Bibliotheken an Schulen e.V.', 'Koordination › Verträge › Vereinsdaten'),
                'verein_kurz' => self::v('Kurzname des Vereins', 'VDBS e.V.', 'Vereinsdaten'),
                'verein_vorsitz' => self::v('Vorsitz', 'Jan Ole Schmiedecke', 'Vereinsdaten'),
                'verein_anschrift' => self::v('Anschrift des Vereins', 'Fritz-Karsen-Schule, Onkel-Bräsig-Straße 76, 12359 Berlin', 'Vereinsdaten'),
                'verein_ort' => self::v('Ort des Vereins', 'Berlin', 'Vereinsdaten (Ort für die Unterschrift)', 'Für Zeilen wie „Berlin, 11.11.2026“.'),
            ]],
            'recipient' => ['label' => 'Empfänger (Schule oder Person)', 'intro' => 'Bei „An die Schule“ stehen hier die Angaben der Schule, bei „An Personen aus dem Team“ die der jeweiligen Person. So funktioniert eine Vorlage für beide.', 'items' => [
                'empfaenger_name' => self::v('Vollständiger Name der Person oder Name der Schule', 'Anna Alt / Gesamtschule am Park', 'Person: Profil oder Kontakt; Schule: Stammdaten'),
                'empfaenger_vorname' => self::v('Vorname', 'Anna', 'Person: Profil oder Kontakt', 'Bei Schulen leer.'),
                'empfaenger_nachname' => self::v('Nachname', 'Alt', 'Person: Profil oder Kontakt', 'Bei Schulen leer.'),
                'empfaenger_anschrift' => self::v('Anschrift in mehreren Zeilen (Straße, dann PLZ und Ort)', 'Musterweg 5
10115 Berlin', 'Person: Profil oder Kontakt; Schule: Stammdaten', 'Steht am besten allein in einem Absatz. Leer, wenn keine Anschrift bekannt ist.'),
                'empfaenger_strasse' => self::v('Straße und Hausnummer', 'Musterweg 5', 'wie Anschrift'),
                'empfaenger_plz' => self::v('Postleitzahl', '10115', 'wie Anschrift'),
                'empfaenger_ort' => self::v('Ort', 'Berlin', 'wie Anschrift'),
                'empfaenger_email' => self::v('E-Mail-Adresse', 'anna@beispiel.example', 'Person: Konto oder Kontakt; Schule: Hauptansprechperson oder Schuladresse'),
                'empfaenger_telefon' => self::v('Telefonnummer', '030 987654', 'Person: Profil oder Kontakt; Schule: Ansprechperson oder Schule', 'Oft leer.'),
            ]],
            'school' => ['label' => 'Schule des Seminars', 'intro' => 'Stehen in allen Schreiben zum Seminar zur Verfügung, auch bei Schreiben an Personen aus dem Team.', 'items' => [
                'schule_name' => self::v('Name der Schule', 'Gesamtschule am Park', 'Schule des Seminars (Schulen)'),
                'schule_anschrift' => self::v('Anschrift der Schule in mehreren Zeilen', 'Schulweg 3
12345 Berlin', 'Schule des Seminars'),
                'schule_telefon' => self::v('Telefonnummer der Schule', '030 123456', 'Schule des Seminars', 'Oft leer.'),
                'schule_email' => self::v('E-Mail-Adresse der Schule', 'sekretariat@schule.example', 'Schule des Seminars', 'Oft leer.'),
                'schule_nummer' => self::v('Interne Kennung der Schule', 'BE/08K06', 'Bundesland und Schulnummer', 'Leer, solange eins von beiden fehlt.'),
                'schule_bundesland' => self::v('Bundesland der Schule', 'Berlin', 'Schule des Seminars'),
                'schule_schulart' => self::v('Schulart', 'Gymnasium', 'Schule des Seminars'),
                'ansprechperson' => self::v('Name der Hauptansprechperson', 'Frau Lehmann', 'Ansprechpersonen der Schule (Hauptperson zuerst)', 'Leer, wenn die Schule keine Ansprechperson hat. Ideal für Wenn-Blöcke.'),
                'ansprechperson_email' => self::v('E-Mail-Adresse der Hauptansprechperson', 'lehmann@schule.example', 'Ansprechpersonen der Schule'),
                'ansprechperson_telefon' => self::v('Telefonnummer der Hauptansprechperson', '030 555555', 'Ansprechpersonen der Schule'),
            ]],
            'seminar' => ['label' => 'Seminar', 'intro' => 'Angaben des Seminars, zu dem Sie das Schreiben erzeugen.', 'items' => [
                'seminar_titel' => self::v('Titel des Seminars', 'SV-Tage Nord', 'Seminar'),
                'seminar_nr' => self::v('Seminarnummer', 'S-2027-0042', 'Seminar'),
                'seminar_art' => self::v('Art des Seminars', 'SV-Tage', 'Seminar'),
                'seminar_termine' => self::v('Alle Termine, jeder in einer eigenen Zeile', '12.05.2027, 09:00–15:00 Uhr – Aula
13.05.2027, 09:00–15:00 Uhr – Aula', 'Termine des Seminars', 'Steht am besten allein in einem Absatz.'),
                'seminar_erster_termin' => self::v('Erster Termin', '12.05.2027, 09:00–15:00 Uhr', 'Termine des Seminars'),
                'seminar_beginn' => self::v('Datum des ersten Termins', '12.05.2027', 'Termine des Seminars'),
                'seminar_ende' => self::v('Datum des letzten Termins', '13.05.2027', 'Termine des Seminars'),
                'seminar_tage' => self::v('Zahl der Tage mit Terminen', '2', 'Termine des Seminars'),
                'seminar_stunden' => self::v('Gesamtdauer aller Termine in Stunden', '12', 'Termine des Seminars', 'Mit Komma bei halben Stunden, z. B. 4,5.'),
                'seminar_ort' => self::v('Ort des ersten Termins', 'Aula', 'Termine des Seminars', 'Leer, wenn der Termin keinen Ort hat.'),
                'seminar_teilnehmende' => self::v('Erwartete Zahl der Teilnehmenden', '40', 'Seminar', 'Leer, wenn nicht eingetragen.'),
                'seminar_team' => self::v('Namen der bestätigten Teamenden, durch Komma getrennt', 'Anna Alt, Ben Bunt', 'Bestätigtes Team des Seminars', 'Leer, solange niemand bestätigt ist.'),
                'seminar_koordination' => self::v('Verantwortliche Koordination', 'Karla Koordina', 'Seminar (Feld „Verantwortliche Koordination“)', 'Leer, wenn nicht festgelegt.'),
                'seminar_frist' => self::v('Stichtag für Interessenbekundungen', '30.04.2027', 'Seminar', 'Leer, wenn kein Stichtag gesetzt ist.'),
                'verguetung' => self::v('Honorar je Person laut Kostenvoranschlag', '150,00 €', 'Kostenvoranschlag des Seminars', 'Ohne Zuschlag für die Seminarleitung. Leer, wenn es keinen Kostenvoranschlag gibt.'),
            ]],
            'condition' => ['label' => 'Bedingungen (nur für Wenn-Blöcke)', 'intro' => 'Diese beiden Platzhalter sind nur zum Ein- und Ausblenden gedacht, siehe Abschnitt „Wenn-Abfragen“.', 'items' => [
                'empfaenger_ist_schule' => self::v('Gefüllt, wenn das Schreiben an die Schule geht', '1 (sonst leer)', 'Wahl „Empfänger“ beim Erzeugen'),
                'empfaenger_ist_person' => self::v('Gefüllt, wenn das Schreiben an eine Person aus dem Team geht', '1 (sonst leer)', 'Wahl „Empfänger“ beim Erzeugen'),
            ]],
        ];
    }

    /**
     * Fertige Wenn-Blöcke zum Kopieren in Word.
     *
     * @return list<array{title: string, purpose: string, lines: list<string>}>
     */
    public static function recipes(): array
    {
        return [
            ['title' => 'Ansprechperson nennen, wenn es eine gibt', 'purpose' => 'Nur anzeigen, wenn die Schule eine Ansprechperson hat.', 'lines' => ['${wenn_ansprechperson}', 'Ihre Ansprechperson: ${ansprechperson} (${ansprechperson_email})', '${/wenn_ansprechperson}']],
            ['title' => 'Ersatztext, wenn es keine gibt', 'purpose' => 'Das Gegenstück: erscheint nur ohne Ansprechperson.', 'lines' => ['${ohne_ansprechperson}', 'Bitte wenden Sie sich an unsere Geschäftsstelle.', '${/ohne_ansprechperson}']],
            ['title' => 'Unterschiedlicher Text für Schulen und Personen', 'purpose' => 'Eine Vorlage für beide Empfängerarten.', 'lines' => ['${wenn_empfaenger_ist_schule}', 'wir bestätigen Ihnen das Seminar an Ihrer Schule.', '${/wenn_empfaenger_ist_schule}', '${wenn_empfaenger_ist_person}', 'vielen Dank für Ihre Mitarbeit beim Seminar.', '${/wenn_empfaenger_ist_person}']],
            ['title' => 'Honorar nur nennen, wenn bekannt', 'purpose' => 'Für Schreiben an Teamende.', 'lines' => ['${wenn_verguetung}', 'Ihr Honorar beträgt ${verguetung}.', '${/wenn_verguetung}']],
            ['title' => 'Hinweis zum Stichtag', 'purpose' => 'Nur, wenn für das Seminar ein Stichtag gesetzt ist.', 'lines' => ['${wenn_seminar_frist}', 'Bitte melden Sie sich bis zum ${seminar_frist}.', '${/wenn_seminar_frist}']],
            ['title' => 'Freitext nur bei Eingabe', 'purpose' => 'Absatz erscheint nur, wenn beim Erzeugen ein freier Text eingegeben wurde.', 'lines' => ['${wenn_freitext}', '${freitext}', '${/wenn_freitext}']],
            ['title' => 'Betreffzeile nur mit Betreff', 'purpose' => 'Blendet die ganze Betreffzeile aus, wenn kein Betreff eingegeben wurde.', 'lines' => ['${wenn_betreff}', 'Betreff: ${betreff}', '${/wenn_betreff}']],
            ['title' => 'Team nennen, wenn schon bestätigt', 'purpose' => 'Für Schreiben an die Schule.', 'lines' => ['${wenn_seminar_team}', 'Ihr Team: ${seminar_team}', '${/wenn_seminar_team}', '${ohne_seminar_team}', 'Das Team wird noch zusammengestellt.', '${/ohne_seminar_team}']],
            ['title' => 'Zwei Bedingungen nacheinander (sonst-Ersatz)', 'purpose' => 'Ein „sonst“ gibt es nicht: Nehmen Sie einen wenn- und einen ohne-Block hintereinander.', 'lines' => ['${wenn_schule_telefon}', 'Telefon: ${schule_telefon}', '${/wenn_schule_telefon}', '${ohne_schule_telefon}', 'Telefon: nicht bekannt', '${/ohne_schule_telefon}']],
            ['title' => 'Verschachtelt: Ansprechperson und Telefon', 'purpose' => 'Ein Block in einem anderen, mit verschiedenen Namen.', 'lines' => ['${wenn_ansprechperson}', 'Ansprechperson: ${ansprechperson}', '${wenn_ansprechperson_telefon}', 'Telefon: ${ansprechperson_telefon}', '${/wenn_ansprechperson_telefon}', '${/wenn_ansprechperson}']],
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
        // array_values: Die Gruppen haben Textschlüssel, die beim Entpacken als benannte Parameter gelten würden.
        return array_values(array_merge(...array_values(array_map(fn (array $group): array => array_keys($group['items']), self::groups()))));
    }
}
