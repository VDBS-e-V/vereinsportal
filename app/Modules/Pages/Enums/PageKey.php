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
            self::Privacy => "{$note}\n\n## Verantwortlicher\n\nVerband für Demokratiebildung und Bibliotheken an Schulen e. V. (Anschrift und Kontakt siehe Impressum).\n\n## Welche Daten wir verarbeiten und wozu\n\n(wird ergänzt)\n\n## Ihre Rechte\n\nSie haben das Recht auf Auskunft, Berichtigung, Löschung, Einschränkung der Verarbeitung, Datenübertragbarkeit und Widerspruch sowie das Recht auf Beschwerde bei einer Aufsichtsbehörde.",
            self::Accessibility => "{$note}\n\n## Stand der Barrierefreiheit\n\n(wird ergänzt)\n\n## Barrieren melden\n\nWenn Ihnen eine Barriere auffällt, schreiben Sie uns über das Kontaktformular.",
            self::Terms => "{$note}\n\n## Geltungsbereich\n\n(wird ergänzt)\n\n## Pflichten der Nutzenden\n\n(wird ergänzt)",
        };
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
