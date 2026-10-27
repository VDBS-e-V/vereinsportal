<?php

namespace App\Modules\Access\Enums;

enum EmailChangeRequestStatus: string
{
    case Pending = 'pending';
    case Superseded = 'superseded';
    case Confirmed = 'confirmed';
}
