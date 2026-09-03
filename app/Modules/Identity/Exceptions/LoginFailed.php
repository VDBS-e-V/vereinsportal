<?php

namespace App\Modules\Identity\Exceptions;

use RuntimeException;

final class LoginFailed extends RuntimeException
{
    public static function invalidCredentials(): self
    {
        return new self(
            'Die eingegebenen Zugangsdaten sind ungültig.'
        );
    }

    public static function temporarilyLocked(): self
    {
        return new self(
            'Die Anmeldung ist vorübergehend nicht möglich. Bitte versuchen Sie es später erneut.'
        );
    }
}