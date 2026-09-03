<?php

namespace App\Modules\Identity\Exceptions;

use RuntimeException;

final class PasswordResetFailed extends RuntimeException
{
    public static function invalidOrExpired(): self
    {
        return new self(
            'Der Link zum Zurücksetzen des Passworts ist ungültig oder abgelaufen.'
        );
    }
}