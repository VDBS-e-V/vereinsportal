<?php

namespace App\Modules\Membership\Enums;

enum MembershipConsentSource: string
{
    case Paper = 'paper';
    case Email = 'email';
    case Portal = 'portal';
    case Administration = 'administration';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Paper => 'Papier / analog',
            self::Email => 'E-Mail',
            self::Portal => 'Portal',
            self::Administration => 'Verwaltung',
            self::Other => 'Sonstiger Weg',
        };
    }
}
