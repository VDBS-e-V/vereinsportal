<?php

namespace App\Modules\Seminars\Enums;

/** Stand einer Bewerbung von Teamenden auf ein Seminar. */
enum ApplicationStatus: string
{
    case Applied = 'applied';
    case Confirmed = 'confirmed';
    case Declined = 'declined';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Applied => 'Interesse bekundet',
            self::Confirmed => 'Im Team',
            self::Declined => 'Nicht berücksichtigt',
            self::Withdrawn => 'Zurückgezogen',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Confirmed => 'success',
            self::Declined => 'error',
            self::Applied => 'warning',
            self::Withdrawn => 'neutral',
        };
    }
}
