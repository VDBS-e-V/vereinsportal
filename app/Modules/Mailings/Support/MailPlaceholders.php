<?php

namespace App\Modules\Mailings\Support;

/**
 * Platzhalter in Betreff und Text der Schreiben, geschrieben als {name}. Je Art des Schreibens gibt es eine feste Liste.
 * Unbekannte Platzhalter werden beim Speichern und Senden abgelehnt, damit nie ein „{tippfehler}“ in einer Mail steht.
 */
final class MailPlaceholders
{
    /** Gilt für jedes Schreiben. @var array<string, string> */
    public const GENERAL = [
        'vorname' => 'Vorname der empfangenden Person',
        'nachname' => 'Nachname der empfangenden Person',
        'name' => 'Vor- und Nachname der empfangenden Person',
        'absender' => 'Name der Person, die das Schreiben verschickt',
        'verein' => 'Name des Vereins',
        'portal' => 'Adresse des Portals',
        'email' => 'E-Mail-Adresse der empfangenden Person',
        'heute' => 'Datum des Versands',
        'jahr' => 'Jahr des Versands',
        'abmelden' => 'Link zum Abbestellen dieser Mitteilungen (leer, wenn die Mail nicht abbestellbar ist; dafür {abmelden|Standardtext} nutzen)',
    ];

    /** Zusätzlich bei Schreiben an Gruppen (aus den Angaben zu Termin oder Versammlung). @var array<string, string> */
    public const MEMBERS = [
        'versammlung' => 'Titel der Versammlung oder des Termins',
        'termin' => 'Termin mit Wochentag, Datum und Uhrzeit',
        'datum' => 'Datum der Versammlung',
        'uhrzeit' => 'Uhrzeit der Versammlung',
        'ort' => 'Ort der Versammlung',
        'link' => 'Link zur Online-Teilnahme oder zu Unterlagen',
        'frist' => 'Anmelde- oder Antragsfrist',
        'wochentag' => 'Wochentag der Versammlung',
        'tage_bis' => 'Anzahl der Tage vom Versand bis zur Versammlung',
    ];

    /** Zusätzlich bei Mitteilungen zu Seminaren. @var array<string, string> */
    public const TEAM = [
        'seminar' => 'Titel des Seminars',
        'seminar_nr' => 'Nummer des Seminars, z. B. S-2027-0042',
        'schule' => 'Name der Schule',
        'termine' => 'Alle Termine des Seminars mit Ort',
        'erster_termin' => 'Der erste Termin des Seminars',
        'ort' => 'Ort des ersten Termins',
        'stichtag' => 'Stichtag für Interessenbekundungen',
        'link' => 'Link zur Seite „Seminare“ im Portal',
        'feedback_link_lehrkraft' => 'Feedbacklink für Lehrkräfte (leer, wenn keiner offen ist)',
        'feedback_link_team' => 'Feedbacklink für Teamende (leer, wenn keiner offen ist)',
        'eigener_text' => 'Der Textabschnitt der Koordination (nur in der Vorlage)',
    ];

    public const KINDS = ['general', 'team'];

    /** {name} oder {name|Standardwert}: Der Standardwert gilt, wenn der Platzhalter leer ist. */
    private const PATTERN = '/\{([a-z_]+)(?:\|([^{}|]{0,60}))?\}/u';

    /** @return array<string, string> Platzhalter => Bedeutung für eine Art von Schreiben */
    public static function definitions(string $kind): array
    {
        return self::GENERAL + match ($kind) {
            'members', 'general' => self::MEMBERS,
            'team' => self::TEAM,
            default => [],
        };
    }

    /** Verwendete Platzhalter, die es für diese Art nicht gibt. @return list<string> */
    public static function unknown(string $text, string $kind): array
    {
        return array_values(array_diff(self::used($text), array_keys(self::definitions($kind))));
    }

    /** Alle in einem Text verwendeten Platzhalter (auch die mit Standardwert {name|Standard}). @return list<string> */
    public static function used(string $text): array
    {
        preg_match_all(self::PATTERN, $text, $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * Setzt die Werte ein. Bekannte Platzhalter ohne Wert bleiben leer (oder nehmen den Standardwert {name|Standard}), unbekannte Schreibweisen bleiben stehen.
     * In HTML werden die Werte maskiert, außer den Schlüsseln in $raw (bereits bereinigtes HTML).
     *
     * @param  array<string, string>  $values
     * @param  list<string>  $raw
     */
    public static function render(string $text, array $values, bool $html = false, array $raw = []): string
    {
        return (string) preg_replace_callback(self::PATTERN, function (array $match) use ($values, $html, $raw): string {
            if (! array_key_exists($match[1], $values)) {
                return $match[0];
            }

            $value = (string) $values[$match[1]];

            if (trim($value) === '' && isset($match[2]) && $match[2] !== '') {
                return $html ? e($match[2]) : $match[2];
            }

            return $html && ! in_array($match[1], $raw, true) ? e($value) : $value;
        }, $text);
    }
}
