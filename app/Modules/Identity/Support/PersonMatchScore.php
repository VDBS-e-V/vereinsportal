<?php

namespace App\Modules\Identity\Support;

use App\Modules\Identity\Models\Person;
use Carbon\CarbonInterface;

final class PersonMatchScore
{
    public static function calculate(
        Person $person,
        string $firstName,
        string $lastName,
        string|CarbonInterface $birthDate,
        string $email,
    ): int {
        $birthDate = $birthDate instanceof CarbonInterface
            ? $birthDate->toDateString()
            : $birthDate;

        $score = 0;

        if ($person->first_name === $firstName) {
            $score++;
        }

        if ($person->last_name === $lastName) {
            $score++;
        }

        if ($person->birth_date->toDateString() === $birthDate) {
            $score++;
        }

        if (
            EmailNormalizer::normalize($person->email)
            === EmailNormalizer::normalize($email)
        ) {
            $score++;
        }

        return $score;
    }

    public static function isPossibleMatch(
        Person $person,
        string $firstName,
        string $lastName,
        string|CarbonInterface $birthDate,
        string $email,
    ): bool {
        return self::calculate(
            person: $person,
            firstName: $firstName,
            lastName: $lastName,
            birthDate: $birthDate,
            email: $email,
        ) >= 3;
    }
}
