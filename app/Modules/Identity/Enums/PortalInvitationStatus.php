<?php

namespace App\Modules\Identity\Enums;

enum PortalInvitationStatus: string
{
    case Open = 'open';
    case Accepted = 'accepted';
    case Expired = 'expired';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Offen',
            self::Accepted => 'Angenommen',
            self::Expired => 'Abgelaufen',
            self::Revoked => 'Widerrufen',
        };
    }
}
