<?php

namespace App\Support;

/**
 * Zentrale Liste der zehn Portal-Bereiche.
 *
 * Bereiche sind rollenbasierte UI-/Permission-Scopes, keine eigenen Module.
 * Start und Konto tragen nur Icon und Label; die acht rollenbasierten Bereiche
 * erhalten zyklisch eine Gruppen-Marke 1-6 (Styleguide 9.2), danach beginnt der
 * Zyklus wieder bei 1.
 */
enum PortalAreas: string
{
    case Start = 'start';
    case Konto = 'konto';
    case Vorstand = 'vorstand';
    case Mitglieder = 'mitglieder';
    case Teamende = 'teamende';
    case Schulen = 'schulen';
    case Koordination = 'koordination';
    case Geschaeftsstelle = 'geschaeftsstelle';
    case Bildungsreferenten = 'bildungsreferenten';
    case Admins = 'admins';

    public const GROUP_MARK_COUNT = 6;

    public function label(): string
    {
        return match ($this) {
            self::Start => 'Start',
            self::Konto => 'Konto',
            self::Vorstand => 'Vorstand',
            self::Mitglieder => 'Mitglieder',
            self::Teamende => 'Teamende',
            self::Schulen => 'Schulen',
            self::Koordination => 'Koordination',
            self::Geschaeftsstelle => 'Geschäftsstelle',
            self::Bildungsreferenten => 'Bildungsreferenten',
            self::Admins => 'Admins',
        };
    }

    /** Name eines Lucide-Icons unter resources/icons/lucide. */
    public function icon(): string
    {
        return match ($this) {
            self::Start => 'house',
            self::Konto => 'user',
            self::Vorstand => 'landmark',
            self::Mitglieder => 'users',
            self::Teamende => 'clipboard-list',
            self::Schulen => 'school',
            self::Koordination => 'layout-dashboard',
            self::Geschaeftsstelle => 'building-2',
            self::Bildungsreferenten => 'graduation-cap',
            self::Admins => 'shield',
        };
    }

    /** Gruppen-Marke 1-6 oder null (Start/Konto). */
    public function groupMark(): ?int
    {
        $position = array_search($this, self::withGroupMark(), true);

        return $position === false
            ? null
            : ($position % self::GROUP_MARK_COUNT) + 1;
    }

    /** @return list<self> */
    public static function withGroupMark(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $area): bool => ! in_array($area, [self::Start, self::Konto], true),
        ));
    }
}
