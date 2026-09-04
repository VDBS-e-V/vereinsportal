<?php

namespace App\Modules\Identity\Support;

use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\URL;

final class PasswordResetUrl
{
    public function create(
        User $user,
        string $token,
    ): string {
        return URL::temporarySignedRoute(
            name: 'my.password.reset',
            expiration: now()->addMinutes(
                (int) config(
                    'auth.passwords.users.expire',
                    60,
                )
            ),
            parameters: [
                'token' => $token,
                'email' => $user->email,
            ],
        );
    }
}
