<?php

namespace App\Modules\Membership\Enums;

enum MembershipStatus: string
{
    case Planned = 'planned';
    case Active = 'active';
    case Ended = 'ended';
}
