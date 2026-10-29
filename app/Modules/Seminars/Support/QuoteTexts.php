<?php

namespace App\Modules\Seminars\Support;

use App\Modules\Seminars\Enums\SeminarType;

/**
 * Texte des gedruckten Kostenvoranschlags (Posten und Inhalt). Die Voreinstellung richtet sich nach Leistung und Eingaben,
 * beide Texte lassen sich im Formular ändern. Der Inhalt hat eine Zeile je Aufzählungspunkt.
 */
final class QuoteTexts
{
    /**
     * @param  array<string, mixed>  $inputs
     * @return array{title: string, content: string}
     */
    public static function defaults(SeminarType $type, array $inputs): array
    {
        $days = (int) ($inputs['days'] ?? 1);
        $team = (int) ($inputs['team'] ?? 1);
        $team = 'Je '.$team.' Teamer:innen'.($type === SeminarType::SvDays ? ' pro Tag' : '');

        return match ($type) {
            SeminarType::SvDays => [
                'title' => 'SV-Tage ('.$days.')',
                'content' => implode("\n", [$team, 'Moderationsmaterial', 'Raum und ggf. Verpflegung werden durch die Schule gestellt']),
            ],
            SeminarType::Coaching => [
                'title' => 'SV-Coaching ('.$days.' Treffen)',
                'content' => implode("\n", [$team, 'Unterstützung von Planung, Organisation und Absprache']),
            ],
            SeminarType::Election => [
                'title' => 'SV-Wahl',
                'content' => implode("\n", [$team, 'Begleitung und Durchführung der Wahl', 'Moderationsmaterial']),
            ],
        };
    }

    /** @return list<string> die Punkte des Inhalts, leere Zeilen entfallen */
    public static function lines(?string $content): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $content) ?: []), fn (string $line): bool => $line !== ''));
    }
}
