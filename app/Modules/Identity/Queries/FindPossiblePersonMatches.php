<?php

namespace App\Modules\Identity\Queries;

use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Support\EmailNormalizer;
use App\Modules\Identity\Support\PersonMatchScore;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

final class FindPossiblePersonMatches
{
    /**
     * @return Collection<int, Person>
     */
    public function execute(
        string $firstName,
        string $lastName,
        string|CarbonInterface $birthDate,
        string $email,
    ): Collection {
        $birthDate = $birthDate instanceof CarbonInterface
            ? $birthDate->toDateString()
            : $birthDate;

        $email = EmailNormalizer::normalize($email);

        $candidates = Person::query()
            ->where(function ($query) use (
                $firstName,
                $lastName,
                $birthDate,
                $email,
            ): void {
                $query
                    ->where(function ($query) use (
                        $firstName,
                        $lastName,
                        $birthDate,
                    ): void {
                        $query
                            ->where('first_name', $firstName)
                            ->where('last_name', $lastName)
                            ->whereDate('birth_date', $birthDate);
                    })
                    ->orWhere(function ($query) use (
                        $firstName,
                        $lastName,
                        $email,
                    ): void {
                        $query
                            ->where('first_name', $firstName)
                            ->where('last_name', $lastName)
                            ->where('email', $email);
                    })
                    ->orWhere(function ($query) use (
                        $firstName,
                        $birthDate,
                        $email,
                    ): void {
                        $query
                            ->where('first_name', $firstName)
                            ->whereDate('birth_date', $birthDate)
                            ->where('email', $email);
                    })
                    ->orWhere(function ($query) use (
                        $lastName,
                        $birthDate,
                        $email,
                    ): void {
                        $query
                            ->where('last_name', $lastName)
                            ->whereDate('birth_date', $birthDate)
                            ->where('email', $email);
                    });
            })
            ->get();

        return $candidates
            ->filter(fn (Person $person): bool => PersonMatchScore::isPossibleMatch(
                person: $person,
                firstName: $firstName,
                lastName: $lastName,
                birthDate: $birthDate,
                email: $email,
            ))
            ->values();
    }
}