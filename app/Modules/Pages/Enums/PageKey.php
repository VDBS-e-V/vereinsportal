<?php

namespace App\Modules\Pages\Enums;

/** Die festen Rechtstext-Seiten des Portals. Der Slug ist Teil der öffentlichen Adresse. */
enum PageKey: string
{
    case Imprint = 'impressum';
    case Privacy = 'datenschutz';
    case Accessibility = 'barrierefreiheit';
    case Terms = 'nutzungsbedingungen';

    public function title(): string
    {
        return match ($this) {
            self::Imprint => 'Impressum',
            self::Privacy => 'Datenschutzerklärung',
            self::Accessibility => 'Barrierefreiheit',
            self::Terms => 'Nutzungsbedingungen',
        };
    }

    public function lead(): string
    {
        return match ($this) {
            self::Imprint => 'Angaben zum Betreiber dieses Portals.',
            self::Privacy => 'Wie wir mit Ihren Daten umgehen.',
            self::Accessibility => 'Erklärung zur Barrierefreiheit dieses Portals.',
            self::Terms => 'Die Regeln für die Nutzung des Portals.',
        };
    }

    /** Muster für die Routen: impressum|datenschutz|… */
    public static function pattern(): string
    {
        return implode('|', array_map(fn (self $key): string => $key->value, self::cases()));
    }

    /** Standardtext, solange die Seite nicht bearbeitet wurde (Markdown). Rechtstexte müssen redaktionell geprüft und ergänzt werden. */
    public function defaultBody(): string
    {
        $note = '*Dieser Text ist ein Platzhalter und muss vom Vorstand ergänzt und rechtlich geprüft werden.*';

        return match ($this) {
            self::Imprint => $this->imprint(),
            self::Privacy => $this->privacy($note),
            self::Accessibility => "{$note}\n\n## Stand der Barrierefreiheit\n\n(wird ergänzt)\n\n## Barrieren melden\n\nWenn Ihnen eine Barriere auffällt, schreiben Sie uns über das Kontaktformular.",
            self::Terms => "{$note}\n\n## Geltungsbereich\n\n(wird ergänzt)\n\n## Pflichten der Nutzenden\n\n(wird ergänzt)",
        };
    }

    /** Entwurf der Datenschutzerklärung nach den tatsächlichen Verarbeitungen des Portals. Muss vom Verein ergänzt und rechtlich geprüft werden. */
    private function privacy(string $note): string
    {
        return $note."

## Verantwortlicher

Verband für Demokratiebildung und Bibliotheken an Schulen e. V. (Anschrift und Kontakt siehe Impressum). Kontakt für Fragen zum Datenschutz: [E-Mail-Adresse eintragen].

## Grundsätze

Wir verarbeiten personenbezogene Daten nur, soweit es für die Arbeit des Vereins und den Betrieb dieses Portals nötig ist. Wir geben Daten nicht zu Werbezwecken weiter. Das Portal läuft bei [Hoster eintragen] in Deutschland. Es setzt nur Cookies, die für die Anmeldung und die Sicherheit nötig sind (Sitzung, Schutz vor Missbrauch); es gibt keine Werbe- oder Analyse-Cookies.

## Konto und Anmeldung

Für ein Konto speichern wir Name, E-Mail-Adresse, Geburtsdatum und Anschrift, Ihr Passwort (nur als Prüfwert), Angaben zur Zwei-Faktor-Anmeldung und Ihre Rollen. Das Portal protokolliert wichtige Vorgänge (zum Beispiel Anmeldungen und Änderungen an Rollen) ohne Inhalte und ohne Passwörter. Rechtsgrundlage ist die Durchführung der Mitgliedschaft oder Zusammenarbeit (Art. 6 Abs. 1 lit. b DSGVO) und unser Interesse an einem sicheren Betrieb (lit. f). Sie können Ihr Konto selbst zur Löschung vorschlagen; nach einer Wartefrist wird es anonymisiert.

## Mitgliedschaft

Für Mitgliedschaftsanträge und Mitglieder speichern wir die Angaben des Antrags, Zeitpunkte, Einwilligungen mit Version und Zeit sowie hochgeladene Unterlagen. Die Daten sind nur für den Vorstand sichtbar. Nach dem Ende der Mitgliedschaft löschen wir sie nach den eingestellten Fristen.

## Seminare, Schulen und Teamende

Für Seminare verarbeiten wir Angaben zu Schulen und Ansprechpersonen, Termine, Kostenvoranschläge und Dokumente. Teamende können freiwillig ein Profil mit Kompetenzen und Erfahrung speichern und sich auf Seminare bewerben; die Koordination nutzt diese Angaben für die Zusammenstellung der Teams. Kontaktdaten anderer Teammitglieder sehen Sie nur, wenn diese zugestimmt haben. Fotos von Teilnehmenden nehmen wir nicht an. Seminardokumente löschen wir nach der Aufbewahrungsfrist.

## Rückmeldungen zu Seminaren

Rückmeldungen über Fragebogen-Links sind anonym: Wir speichern keine IP-Adressen und keine Zugangsdaten zur Antwort. Ergebnisse zeigen wir erst ab einer Mindestzahl von Antworten. Nach der Aufbewahrungsfrist werden Einzelantworten und Freitexte gelöscht; nur Zahlen bleiben.

## Kostenerstattung und Beschaffung

Für Anträge auf Erstattung von Auslagen und Fahrtkosten sowie für Bestellungen verarbeiten wir Name, Angaben zum Antrag, Belege und für Erstattungen die Bankverbindung (IBAN). Die Bankverbindung wird verschlüsselt gespeichert und 30 Tage nach der Auszahlung gelöscht. Anträge und Belege bewahren wir 10 Jahre auf, weil sie Buchungsunterlagen sind (gesetzliche Aufbewahrungspflicht, Art. 6 Abs. 1 lit. c DSGVO).

## Honorarverträge

Für Dienstleistungsverträge mit Teamenden verarbeiten wir Name, Anschrift, Steuernummer und Bankverbindung. Die Angaben sind freiwillig im Konto gespeichert oder werden für den einzelnen Vertrag erfasst, liegen verschlüsselt und werden 10 Jahre nach dem letzten Seminartermin gelöscht (Aufbewahrungspflicht für Abrechnungsunterlagen).

## Vorstandssitzungen und Gäste

Der Vorstand plant seine Sitzungen im Portal. Dafür speichern wir Termin, Ort, Tagesordnung, die Anwesenheit der Vorstandsmitglieder, das Protokoll und Anhänge. Diese Angaben sieht nur der Vorstand. Protokolle bewahren wir als Vereinsunterlagen auf, solange sie für die Arbeit des Vereins nötig sind. Rechtsgrundlage ist die Organisation der Vereinsarbeit (Art. 6 Abs. 1 lit. b und f DSGVO). Die Einladung der Vorstandsmitglieder per E-Mail enthält eine Kalenderdatei; sie ist abschaltbar, und einzelne Mitglieder können sie in ihrem Konto abstellen.

Für externe Gäste einer Sitzung speichern wir Name, E-Mail-Adresse und gegebenenfalls Ihre Organisation, damit der Vorstand Sie einladen kann (Art. 6 Abs. 1 lit. f DSGVO, unser Interesse an der Durchführung der Sitzung). Die Angaben tragen Vorstandsmitglieder ein, sie stammen aus dem Kontakt mit Ihnen. Gäste haben keinen Zugang zum Portal. Jeder Gast erhält die Einladung einzeln, ohne dass andere Gäste die Adresse sehen; die Tagesordnung steht nur in der E-Mail, wenn der Vorstand sie ausdrücklich mitsendet. Wir löschen die Angaben der Gäste 30 Tage nach der Sitzung automatisch; auf Wunsch löschen wir sie sofort. Wenn Ihr Name im Protokoll vorkommt, bleibt das Protokoll als Vereinsunterlage bestehen.

Der Kalender der Vorstandssitzungen kann als Abo in Kalenderprogrammen genutzt werden. Die geheime Abo-Adresse enthält nur Titel, Zeit, Ort und gegebenenfalls den Link zur Videokonferenz, nie Tagesordnung oder Protokoll. Der Vorstand kann die Adresse jederzeit erneuern; die alte Adresse funktioniert dann nicht mehr.

## Aufgabenverwaltung

Für die interne Zusammenarbeit führen wir Aufgaben in Projekten. Gespeichert werden Titel, Beschreibung, Priorität, Fälligkeit, der Status, die zuständigen Konten, Kommentare mit Name und Zeit sowie Änderungen im Protokoll (ohne Inhalte). Projekte sehen nur ihre Mitglieder; Aufgaben des Vorstands nur der Vorstand. Rechtsgrundlage ist die Organisation der Vereinsarbeit (Art. 6 Abs. 1 lit. b und f DSGVO). Dazu kommen Labels, Unteraufgaben und Anhänge (privat gespeichert, nur für die Mitglieder des Projekts). E-Mails zu Aufgaben (neue Zuweisung, Erinnerung an die Fälligkeit) senden wir nur, wenn Sie sie in Ihrem Konto selbst eingeschaltet haben; sie nennen den Titel der Aufgabe und das Projekt. Aufgaben, Anhänge und Kommentare bleiben, bis sie oder das Projekt gelöscht werden; wird ein Konto anonymisiert, verschwindet der Name aus Zuweisungen und Kommentaren.

## E-Mails und Rundschreiben

Wir schreiben Ihnen E-Mails, die für Ihre Rolle nötig sind (zum Beispiel Einladungen zur Mitgliederversammlung, Hinweise zu Seminaren). Mitteilungen an Teamende, Schulen und Kontakte können Sie über den Link in der E-Mail oder in Ihren Einstellungen abbestellen. Vereinspost an Mitglieder (zum Beispiel die Einladung zur Versammlung) ist von der Abmeldung ausgenommen, weil der Verein dazu verpflichtet ist. Die Zustelllisten (Adressen und Zustellstatus) löschen wir nach 90 Tagen; Texte und Zahlen der Schreiben bleiben ohne Empfängerliste erhalten.

## Anmeldung bei anderen Systemen

Wenn Sie sich über das Portal bei Nextcloud, Moodle oder anderen angebundenen Systemen anmelden, übermitteln wir nur Name, E-Mail-Adresse, eine feste Kennung und Ihre Gruppen (Rollen), niemals Anschrift, Geburtsdatum oder Telefonnummer. Welche Anwendungen Zugriff haben, sehen Sie in Ihrem Konto unter „Verbundene Anwendungen“ und können es dort beenden.

## Empfänger

Ihre Daten sehen nur Personen, die sie für ihre Aufgabe brauchen (Vorstand, Geschäftsstelle, Koordination, Kassenführung, Geschäftsführung), gestuft nach Rollen. Auftragsverarbeiter ist der Hosting-Anbieter [Hoster eintragen] mit Vertrag zur Auftragsverarbeitung. Eine Übermittlung in Drittländer findet nicht statt.

## Ihre Rechte

Sie haben das Recht auf Auskunft, Berichtigung, Löschung, Einschränkung der Verarbeitung, Datenübertragbarkeit und Widerspruch sowie das Recht auf Beschwerde bei einer Aufsichtsbehörde (zuständig ist die Berliner Beauftragte für Datenschutz und Informationsfreiheit). Einwilligungen können Sie jederzeit mit Wirkung für die Zukunft widerrufen.";
    }

    private function imprint(): string
    {
        $footer = (array) config('portal.letter.footer', []);
        $lines = fn (string $key): string => implode("  \n", (array) ($footer[$key] ?? []));

        return "## Angaben\n\n".($footer['organization'] ?? 'Verband für Demokratiebildung und Bibliotheken an Schulen e. V.')
            ."  \n".$lines('address_lines')
            ."\n\n## Vertretung\n\n".($footer['chair_label'] ?? 'Vorsitzende/r:').' '.($footer['chair'] ?? '')
            ."\n\n## Kontakt\n\n".$lines('contact_lines')
            ."\n\n*Bitte um Registereintrag und weitere Pflichtangaben ergänzen.*";
    }
}
