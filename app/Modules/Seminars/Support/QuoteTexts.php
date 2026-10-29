<?php

namespace App\Modules\Seminars\Support;

use App\Modules\Seminars\Enums\SeminarType;
use App\Modules\Seminars\Models\SeminarProductText;

/**
 * Texte des gedruckten Kostenvoranschlags. Titel und Standardbeschreibung je Produkt sind globale Werte (Seite „Texte“, Tabelle
 * seminar_product_texts) mit Platzhaltern, die aus den Angaben der Position gefüllt werden. Im Kostenrechner lässt sich nur eine
 * Ergänzung hinzufügen. Der Inhalt hat eine Zeile je Aufzählungspunkt.
 */
final class QuoteTexts
{
    /** @var array<string, string> Platzhalter => Bedeutung */
    public const PLACEHOLDERS = [
        'stueckzahl' => 'Stückzahl der Position (z. B. 3)',
        'einheit' => 'Einheit des Produkts (Tage, Treffen oder Sitzungen)',
        'teamende' => 'Anzahl der Teamenden',
        'stunden' => 'Stunden pro Tag',
        'gruppe' => 'Gruppengröße (Teilnehmende)',
        'leitung' => 'Anzahl der Seminarleitungen',
        'produkt' => 'Name des Produkts (z. B. SV-Tage)',
    ];

    /**
     * Eingebaute Texte, falls für ein Produkt noch nichts gespeichert ist.
     *
     * @return array{title: string, content: string}
     */
    public static function builtin(SeminarType $type): array
    {
        return match ($type) {
            SeminarType::SvDays => ['title' => 'SV-Tage ({stueckzahl})', 'content' => "Je {teamende} Teamer:innen pro Tag\nModerationsmaterial\nRaum und ggf. Verpflegung werden durch die Schule gestellt"],
            SeminarType::Coaching => ['title' => 'SV-Coaching ({stueckzahl} {einheit})', 'content' => "Je {teamende} Teamer:innen\nUnterstützung von Planung, Organisation und Absprache"],
            SeminarType::Election => ['title' => 'SV-Wahl', 'content' => "Je {teamende} Teamer:innen\nBegleitung und Durchführung der Wahl\nModerationsmaterial"],
            SeminarType::GsvSupport => ['title' => 'Begleitung GSV-Sitzungen ({stueckzahl} {einheit})', 'content' => 'Unterstützung der Planung und Organisation, sowie Unterstützung vor Ort'],
        };
    }

    /**
     * Die gültige Vorlage mit Platzhaltern (gespeicherter Wert, sonst der eingebaute).
     *
     * @return array{title: string, content: string}
     */
    public static function template(SeminarType $type): array
    {
        $saved = SeminarProductText::query()->where('product', $type->value)->first();

        return $saved !== null ? ['title' => $saved->title, 'content' => $saved->content] : self::builtin($type);
    }

    /**
     * Titel und Beschreibung des Produkts mit eingesetzten Platzhaltern.
     *
     * @param  array<string, mixed>  $inputs  Eingaben der Position (days, team, hours, group, leaders)
     * @return array{title: string, content: string}
     */
    public static function defaults(SeminarType $type, array $inputs): array
    {
        $template = self::template($type);

        return ['title' => self::render($template['title'], $type, $inputs), 'content' => self::render($template['content'], $type, $inputs)];
    }

    /** @param  array<string, mixed>  $inputs */
    public static function render(string $template, SeminarType $type, array $inputs): string
    {
        $values = [
            'stueckzahl' => (string) (int) ($inputs['days'] ?? 1),
            'einheit' => $type->unit(),
            'teamende' => (string) (int) ($inputs['team'] ?? 1),
            'stunden' => rtrim(rtrim(number_format((float) ($inputs['hours'] ?? 0), 1, ',', ''), '0'), ','),
            'gruppe' => (string) (int) ($inputs['group'] ?? 0),
            'leitung' => (string) (int) ($inputs['leaders'] ?? 0),
            'produkt' => $type->label(),
        ];

        return (string) preg_replace_callback('/\{(\w+)\}/', fn (array $match): string => $values[$match[1]] ?? $match[0], $template);
    }

    /** @return list<string> Platzhalter im Text, die es nicht gibt */
    public static function unknownPlaceholders(string $text): array
    {
        preg_match_all('/\{(\w+)\}/', $text, $matches);

        return array_values(array_unique(array_filter($matches[1], fn (string $name): bool => ! isset(self::PLACEHOLDERS[$name]))));
    }

    /** @return list<string> die Punkte des Inhalts, leere Zeilen entfallen */
    public static function lines(?string $content): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $content) ?: []), fn (string $line): bool => $line !== ''));
    }
}
