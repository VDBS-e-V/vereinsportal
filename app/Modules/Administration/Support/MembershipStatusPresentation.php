<?php

namespace App\Modules\Administration\Support;

use App\Modules\Membership\Enums\MembershipStatus;

final class MembershipStatusPresentation
{
    public static function label(MembershipStatus $status): string
    {
        return match ($status) {
            MembershipStatus::Planned => 'Geplant',
            MembershipStatus::Active => 'Aktiv',
            MembershipStatus::Ended => 'Beendet',
        };
    }

    public static function type(MembershipStatus $status): string
    {
        return match ($status) {
            MembershipStatus::Planned => 'info',
            MembershipStatus::Active => 'success',
            MembershipStatus::Ended => 'neutral',
        };
    }
}
