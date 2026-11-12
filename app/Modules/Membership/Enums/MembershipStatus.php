<?php

namespace App\Modules\Membership\Enums;

enum MembershipStatus: string
{
    case Requested = 'requested';
    case Active = 'active';
    case Ended = 'ended';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Beantragt',
            self::Active => 'Aktiv',
            self::Ended => 'Beendet',
            self::Rejected => 'Abgelehnt',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Requested => 'warning',
            self::Active => 'success',
            self::Ended => 'neutral',
            self::Rejected => 'error',
        };
    }

    /** Beantragt oder laufend: pro Person höchstens eine solche Mitgliedschaft. */
    public function isOpen(): bool
    {
        return $this === self::Requested || $this === self::Active;
    }
}
