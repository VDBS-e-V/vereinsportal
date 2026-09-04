<?php

namespace App\Modules\Identity\Exceptions;

use RuntimeException;

final class RegistrationCannotComplete extends RuntimeException
{
    public static function invalidOrExpired(): self
    {
        return new self(
            'Der Registrierungslink ist ungültig oder abgelaufen.'
        );
    }

    public static function possibleDuplicate(): self
    {
        return new self(
            'Zu den eingegebenen Daten existiert möglicherweise bereits ein Datensatz.'
        );
    }

    public static function emailUnavailable(): self
    {
        return new self(
            'Die Registrierung kann mit dieser E-Mail-Adresse nicht abgeschlossen werden.'
        );
    }
}
