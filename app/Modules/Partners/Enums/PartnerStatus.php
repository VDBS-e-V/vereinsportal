<?php

namespace App\Modules\Partners\Enums;

enum PartnerStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktiv',
            self::Inactive => 'Inaktiv',
        };
    }

    public function tone(): string
    {
        return $this === self::Active ? 'success' : 'neutral';
    }
}
