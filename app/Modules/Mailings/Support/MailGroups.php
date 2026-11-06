<?php

namespace App\Modules\Mailings\Support;

use App\Modules\Access\Models\User;

/**
 * Die Gruppen, an die Vorstand und Geschäftsstelle schreiben können. Mitglieder kommen aus den Mitgliedschaften (Vereinspost, nicht abbestellbar),
 * die übrigen Gruppen sind Konten mit der jeweiligen Rolle. Teamende und Schulen können diese Mitteilungen in ihren Einstellungen abbestellen.
 */
final class MailGroups
{
    /**
     * @var array<string, array{label: string, description: string, role: ?string, optout: ?string, fallback: string, ability: string}>
     */
    public const GROUPS = [
        'members' => ['label' => 'Mitglieder', 'description' => 'Alle laufenden Mitgliedschaften mit E-Mail-Adresse (nicht abbestellbar)', 'role' => null, 'optout' => null, 'fallback' => 'Mitglied', 'ability' => 'mailings.members'],
        'team' => ['label' => 'Teamende', 'description' => 'Alle aktiven Konten mit der Rolle Team', 'role' => 'team', 'optout' => 'team_mailings', 'fallback' => 'Teamer:in', 'ability' => 'mailings.send'],
        'schools' => ['label' => 'Schulen', 'description' => 'Alle aktiven Schulkonten', 'role' => 'school', 'optout' => 'school_mailings', 'fallback' => 'Schule', 'ability' => 'mailings.send'],
        'coordination' => ['label' => 'Koordination', 'description' => 'Konten mit der Rolle Koordination', 'role' => 'coordination', 'optout' => null, 'fallback' => 'Kolleg:in', 'ability' => 'mailings.send'],
        'education' => ['label' => 'Bildungsreferenten', 'description' => 'Konten mit der Rolle Bildungsreferenten', 'role' => 'education_coordination', 'optout' => null, 'fallback' => 'Kolleg:in', 'ability' => 'mailings.send'],
        'board' => ['label' => 'Vorstand', 'description' => 'Konten mit der Rolle Vorstand', 'role' => 'board_member', 'optout' => null, 'fallback' => 'Kolleg:in', 'ability' => 'mailings.send'],
        'office' => ['label' => 'Geschäftsstelle', 'description' => 'Konten mit der Rolle Geschäftsstelle', 'role' => 'administration_staff', 'optout' => null, 'fallback' => 'Kolleg:in', 'ability' => 'mailings.send'],
    ];

    /** Gruppen, die die Koordination anschreiben darf (Recht mailings.send_limited): keine Mitglieder, kein Vorstand, keine Geschäftsstelle. */
    public const LIMITED = ['team', 'schools', 'coordination', 'education'];

    /** Themen der Abmeldung. Interne Gruppen (Koordination, Bildungsreferenten, Vorstand, Geschäftsstelle) haben keines: Organisationspost ist nicht abbestellbar. */
    public const TOPICS = [
        'member_info' => 'Mitteilungen des Vereins an Mitglieder',
        'team_mailings' => 'Mitteilungen an Teamende',
        'school_mailings' => 'Mitteilungen an Schulen',
    ];

    public static function topicFor(?string $group): ?string
    {
        return match ($group) {
            'members' => 'member_info',
            'team' => 'team_mailings',
            'schools' => 'school_mailings',
            default => null,
        };
    }

    /** Gruppen, die diese Person anschreiben darf. @return array<string, array{label: string, description: string}> */
    public static function allowedFor(User $user): array
    {
        $groups = [];

        foreach (self::GROUPS as $key => $group) {
            if ($user->can($group['ability']) || ($user->can('mailings.send_limited') && in_array($key, self::LIMITED, true))) {
                $groups[$key] = ['label' => $group['label'], 'description' => $group['description']];
            }
        }

        return $groups;
    }

    public static function label(string $key): string
    {
        return self::GROUPS[$key]['label'] ?? $key;
    }

    /** @param  list<string>  $keys */
    public static function labels(array $keys): string
    {
        return implode(', ', array_map(fn (string $key): string => self::label($key), $keys));
    }
}
