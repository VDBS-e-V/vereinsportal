<?php

namespace App\Modules\Access\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case PendingVerification = 'pending_verification';
    case Disabled = 'disabled';
    case PendingDeletion = 'pending_deletion';
    case Anonymized = 'anonymized';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktiv',
            self::PendingVerification => 'Eingeladen',
            self::Disabled => 'Gesperrt',
            self::PendingDeletion => 'Löschung angefragt',
            self::Anonymized => 'Anonymisiert',
        };
    }

    /** Farbton des Badges: success | warning | error | neutral. */
    public function tone(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::PendingVerification, self::PendingDeletion => 'warning',
            self::Disabled => 'error',
            self::Anonymized => 'neutral',
        };
    }
}
