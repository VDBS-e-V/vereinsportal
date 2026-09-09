<?php

namespace App\Modules\Administration\Support;

use App\Modules\Identity\Enums\UserStatus;

final class UserStatusPresentation
{
    public static function label(UserStatus $status): string
    {
        return match ($status) {
            UserStatus::Active => 'Aktiv',
            UserStatus::PendingVerification => 'Bestätigung ausstehend',
            UserStatus::Disabled => 'Deaktiviert',
            UserStatus::PendingDeletion => 'Löschung vorgemerkt',
            UserStatus::Anonymized => 'Anonymisiert',
        };
    }

    public static function type(UserStatus $status): string
    {
        return match ($status) {
            UserStatus::Active => 'success',
            UserStatus::PendingVerification => 'warning',
            UserStatus::Disabled => 'danger',
            UserStatus::PendingDeletion => 'danger',
            UserStatus::Anonymized => 'info',
        };
    }
}
