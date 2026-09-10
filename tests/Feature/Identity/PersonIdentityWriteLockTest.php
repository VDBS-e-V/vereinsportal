<?php

use App\Modules\Identity\Support\PersonIdentityWriteLock;
use Illuminate\Support\Facades\DB;

it('serializes identities that share a possible duplicate combination', function () {
    $defaultConnection = config('database.default');
    $connectionConfig = is_string($defaultConnection)
        ? config('database.connections.'.$defaultConnection)
        : null;

    expect($defaultConnection)->toBeString()
        ->and($connectionConfig)->toBeArray();

    config([
        'database.connections.person_lock_test' => $connectionConfig,
    ]);

    DB::purge('person_lock_test');
    $secondaryConnection = DB::connection('person_lock_test');

    $sharedCombination = [
        'first_name',
        'last_name',
        'birth_date',
        'Erika',
        'Muster',
        '1990-04-12',
    ];

    $lockName = 'vdbs-person:'.substr(
        hash(
            'sha256',
            json_encode(
                $sharedCombination,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE,
            ),
        ),
        0,
        52,
    );

    $blockedWhileHeld = app(PersonIdentityWriteLock::class)->execute(
        firstName: 'Erika',
        lastName: 'Muster',
        birthDate: '1990-04-12',
        email: 'first@example.test',
        callback: function () use ($secondaryConnection, $lockName): int {
            $result = (array) ($secondaryConnection->selectOne(
                'SELECT GET_LOCK(?, 0) AS acquired',
                [$lockName],
            ) ?? []);

            return (int) ($result['acquired'] ?? 0);
        },
    );

    expect($blockedWhileHeld)->toBe(0);

    $resultAfterRelease = (array) ($secondaryConnection->selectOne(
        'SELECT GET_LOCK(?, 0) AS acquired',
        [$lockName],
    ) ?? []);

    expect((int) ($resultAfterRelease['acquired'] ?? 0))->toBe(1);

    $secondaryConnection->selectOne(
        'SELECT RELEASE_LOCK(?) AS released',
        [$lockName],
    );

    DB::disconnect('person_lock_test');
});
