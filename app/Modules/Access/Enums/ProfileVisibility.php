<?php

namespace App\Modules\Access\Enums;

/** Wer eine Angabe des Profils sehen darf. */
enum ProfileVisibility: string
{
    case Private = 'private';
    case Portal = 'portal';
    case Public = 'public';

    public function label(): string
    {
        return match ($this) {
            self::Private => 'Nur ich',
            self::Portal => 'Angemeldete Portalnutzer',
            self::Public => 'Öffentlich',
        };
    }

    /** Darf ein Betrachter (null = nicht angemeldet) die Angabe sehen? */
    public function allows(bool $loggedIn): bool
    {
        return match ($this) {
            self::Public => true,
            self::Portal => $loggedIn,
            self::Private => false,
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
