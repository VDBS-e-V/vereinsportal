<?php

namespace App\Modules\Identity\Support;

use Closure;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class EmailIdentityWriteLock
{
    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function execute(string $email, Closure $callback): mixed
    {
        $normalizedEmail = EmailNormalizer::normalize($email);
        $lockName = 'vdbs-email:'.substr(
            hash('sha256', $normalizedEmail),
            0,
            52,
        );

        $connection = DB::connection();
        $result = (array) ($connection->selectOne(
            'SELECT GET_LOCK(?, ?) AS acquired',
            [$lockName, 10],
        ) ?? []);

        if ((int) ($result['acquired'] ?? 0) !== 1) {
            throw new RuntimeException(
                'E-Mail-Schreibsperre konnte nicht rechtzeitig erworben werden.',
            );
        }

        try {
            return $callback();
        } finally {
            $connection->selectOne(
                'SELECT RELEASE_LOCK(?) AS released',
                [$lockName],
            );
        }
    }
}
