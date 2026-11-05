<?php

namespace App\Modules\Mailings\Support;

/** Feste Kategorien für Mailvorlagen und Textbausteine (Auswahl statt freier Eingabe, damit die Ordnung erhalten bleibt). */
final class MailCategories
{
    /** @var array<string, string> */
    public const TEMPLATES = [
        'versammlung' => 'Versammlung & Mitglieder',
        'schulen' => 'Schulen & Seminare',
        'team' => 'Team',
        'feedback' => 'Feedback',
        'allgemein' => 'Allgemein',
    ];

    /** @var array<string, string> */
    public const SNIPPETS = [
        'anrede' => 'Anrede & Gruß',
        'termine' => 'Termine & Orte',
        'fristen' => 'Anmeldung & Fristen',
        'datenschutz' => 'Datenschutz & Abmeldung',
        'kontakt' => 'Kontakt',
        'seminare' => 'Seminare',
        'allgemein' => 'Allgemein',
    ];

    /** @var array<string, string> */
    public const AUDIENCES = [
        'members' => 'Mitglieder',
        'team' => 'Teamende',
        'schools' => 'Schulen',
        'all' => 'Alle',
    ];

    public static function templateLabel(?string $key): string
    {
        return self::TEMPLATES[$key ?? ''] ?? 'Ohne Kategorie';
    }

    public static function snippetLabel(?string $key): string
    {
        return self::SNIPPETS[$key ?? ''] ?? 'Ohne Kategorie';
    }
}
