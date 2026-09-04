<?php

namespace App\Modules\Identity\Enums;

enum EmailChangeRequestStatus: string
{
    case Pending = 'pending';
    case Superseded = 'superseded';
    case Confirmed = 'confirmed';
}
