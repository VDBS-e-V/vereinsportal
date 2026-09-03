<?php

namespace App\Modules\Identity\Exceptions;

use RuntimeException;

final class RegistrationCannotStart extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'Zu den eingegebenen Daten existiert möglicherweise bereits ein Datensatz.'
        );
    }
}