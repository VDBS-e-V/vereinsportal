<?php

namespace App\Modules\Access\Enums;

enum TwoFactorMethodType: string
{
    case Email = 'email';
    case Totp = 'totp';
}
