<?php

use App\Modules\Identity\Support\EmailIdentityWriteLock;
use App\Modules\Identity\Support\EmailNormalizer;
use Illuminate\Support\Facades\DB;

it('holds the normalized email write lock across database connections', function () {
    $defaultConnection = config('database.default');
    $connectionConfig = is_string($defaultConnection)
        ? config('database.connections.'.$defaultConnection)
        : null;

    expect($defaultConnection)->toBeString()
        ->and($connectionConfig)->toBeArray();

    config([
        'database.connections.email_lock_test' => $connectionConfig,
    ]);

    DB::purge('email_lock_test');
    $secondaryConnection = DB::connection('email_lock_test');

    $email = '  LOCK.TEST@EXAMPLE.TEST ';
    $lockName = 'vdbs-email:'.substr(
        hash('sha256', EmailNormalizer::normalize($email)),
        0,
        52,
    );

    $blockedWhileHeld = app(EmailIdentityWriteLock::class)->execute(
        $email,
        function () use ($secondaryConnection, $lockName): int {
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

    DB::disconnect('email_lock_test');
});
