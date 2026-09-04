<?php

namespace App\Modules\Identity\Support;

use Illuminate\Validation\Rules\Password;

final class PasswordRules
{
    public static function default(): Password
    {
        return Password::min(10)
            ->mixedCase()
            ->numbers()
            ->symbols();
    }
}
