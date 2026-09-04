<?php

namespace App\Modules\Identity\Exceptions;

use RuntimeException;

final class EmailChangeCannotStart extends RuntimeException
{
    public static function emailUnavailable(): self
    {
        return new self(
            'Diese E-Mail-Adresse kann nicht verwendet werden.'
        );
    }

    public static function accountUnavailable(): self
    {
        return new self(
            'Die E-Mail-Adresse kann derzeit nicht geändert werden.'
        );
    }
}
