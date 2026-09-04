<?php

namespace App\Modules\Identity\Exceptions;

use RuntimeException;

final class TwoFactorChallengeFailed extends RuntimeException
{
    public static function invalidCode(): self
    {
        return new self(
            'Der eingegebene Sicherheitscode ist nicht gültig.'
        );
    }

    public static function locked(): self
    {
        return new self(
            'Zu viele fehlgeschlagene Versuche. Bitte versuchen Sie es später erneut.'
        );
    }

    public static function unavailable(): self
    {
        return new self(
            'Diese Zwei-Faktor-Methode ist derzeit nicht verfügbar.'
        );
    }
}
