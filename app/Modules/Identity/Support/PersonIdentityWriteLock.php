<?php

namespace App\Modules\Identity\Support;

use Closure;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use JsonException;
use RuntimeException;

final class PersonIdentityWriteLock
{
    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     *
     * @throws JsonException
     */
    public function execute(
        string $firstName,
        string $lastName,
        string $birthDate,
        string $email,
        Closure $callback,
    ): mixed {
        $connection = DB::connection();
        $acquiredLocks = [];

        foreach ($this->lockNames(
            $firstName,
            $lastName,
            $birthDate,
            $email,
        ) as $lockName) {
            $result = (array) ($connection->selectOne(
                'SELECT GET_LOCK(?, ?) AS acquired',
                [$lockName, 10],
            ) ?? []);

            if ((int) ($result['acquired'] ?? 0) !== 1) {
                $this->releaseLocks(
                    $connection,
                    $acquiredLocks,
                );

                throw new RuntimeException(
                    'Personen-Schreibsperre konnte nicht rechtzeitig erworben werden.',
                );
            }

            $acquiredLocks[] = $lockName;
        }

        try {
            return $callback();
        } finally {
            $this->releaseLocks(
                $connection,
                $acquiredLocks,
            );
        }
    }

    /**
     * @return list<string>
     *
     * @throws JsonException
     */
    private function lockNames(
        string $firstName,
        string $lastName,
        string $birthDate,
        string $email,
    ): array {
        $email = EmailNormalizer::normalize($email);

        $combinations = [
            ['first_name', 'last_name', 'birth_date', $firstName, $lastName, $birthDate],
            ['first_name', 'last_name', 'email', $firstName, $lastName, $email],
            ['first_name', 'birth_date', 'email', $firstName, $birthDate, $email],
            ['last_name', 'birth_date', 'email', $lastName, $birthDate, $email],
        ];

        $lockNames = array_map(
            static function (array $combination): string {
                $payload = json_encode(
                    $combination,
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE,
                );

                return 'vdbs-person:'.substr(
                    hash('sha256', $payload),
                    0,
                    52,
                );
            },
            $combinations,
        );

        sort($lockNames, SORT_STRING);

        return array_values(array_unique($lockNames));
    }

    /**
     * @param  list<string>  $lockNames
     */
    private function releaseLocks(
        ConnectionInterface $connection,
        array $lockNames,
    ): void {
        foreach (array_reverse($lockNames) as $lockName) {
            $connection->selectOne(
                'SELECT RELEASE_LOCK(?) AS released',
                [$lockName],
            );
        }
    }
}
