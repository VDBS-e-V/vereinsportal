<?php

namespace App\Modules\Identity\Enums;

enum TwoFactorMethodType: string
{
    case Email = 'email';
    case Totp = 'totp';
}