<?php

namespace App\Modules\Identity\Exceptions;

use RuntimeException;

final class EmailChangeCannotComplete extends RuntimeException
{
    public static function invalidOrExpired(): self
    {
        return new self(
            'Diese E-Mail-Änderung ist ungültig oder abgelaufen.'
        );
    }

    public static function emailUnavailable(): self
    {
        return new self(
            'Die neue E-Mail-Adresse kann nicht mehr verwendet werden.'
        );
    }
}