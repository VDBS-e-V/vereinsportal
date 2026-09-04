<?php

namespace App\Modules\Identity\Exceptions;

use RuntimeException;

final class RegistrationVerificationCannotBeResent extends RuntimeException
{
    public static function unavailable(): self
    {
        return new self(
            'Eine neue Bestätigungs-E-Mail kann derzeit nicht versendet werden.'
        );
    }

    public static function rateLimited(): self
    {
        return new self(
            'Bitte warten Sie mindestens eine Minute, bevor Sie die Bestätigungs-E-Mail erneut anfordern.'
        );
    }
}
