<?php

namespace App\Modules\Identity\Exceptions;

use RuntimeException;

final class TwoFactorSetupFailed extends RuntimeException
{
    public static function invalidCode(): self
    {
        return new self(
            'Der eingegebene Code ist nicht gültig.'
        );
    }

    public static function invalidMethod(): self
    {
        return new self(
            'Die Zwei-Faktor-Methode kann nicht verwendet werden.'
        );
    }
}
