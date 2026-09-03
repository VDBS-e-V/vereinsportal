<?php

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Actions\Auth\AttemptLoginAction;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Exceptions\LoginFailed;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\LoginRateLimiter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

function makeLoginUser(
    array $attributes = [],
): User {
    $emailVerifiedAt = array_key_exists(
        'email_verified_at',
        $attributes,
    )
        ? $attributes['email_verified_at']
        : now();

    unset($attributes['email_verified_at']);

    $user = User::query()->create(
        array_merge([
            'email' => 'erika@example.test',
            'password' => 'Sicher123!',
            'status' => UserStatus::Active,
            'session_version' => 1,
        ], $attributes)
    );

    $user->email_verified_at = $emailVerifiedAt;
    $user->save();

    return $user->refresh();
}

beforeEach(function () {
    RateLimiter::clear(
        'identity:login:user:'
        .hash('sha256', 'erika@example.test')
    );
});

it('logs in an active verified user', function () {
    $user = makeLoginUser();

    app(AttemptLoginAction::class)->execute(
        email: 'erika@example.test',
        password: 'Sicher123!',
        remember: false,
        ipAddress: '127.0.0.1',
        userAgent: 'Pest Browser',
    );

    expect(Auth::id())
        ->toBe($user->id)
        ->and(
            session('identity.session_version')
        )
        ->toBe(1)
        ->and(
            User::query()
                ->findOrFail($user->id)
                ->last_login_at
        )
        ->not->toBeNull()
        ->and(
            AuditEvent::query()
                ->where(
                    'event_key',
                    AuditEventCatalog::AUTH_LOGIN_SUCCEEDED,
                )
                ->count()
        )
        ->toBe(1);
});

it('normalizes email before login', function () {
    $user = makeLoginUser();

    app(AttemptLoginAction::class)->execute(
        email: '  ERIKA@EXAMPLE.TEST  ',
        password: 'Sicher123!',
        remember: false,
        ipAddress: '127.0.0.1',
    );

    expect(Auth::id())
        ->toBe($user->id);
});

it('rejects a wrong password neutrally', function () {
    makeLoginUser();

    expect(
        fn () => app(
            AttemptLoginAction::class
        )->execute(
            email: 'erika@example.test',
            password: 'Falsch123!',
            remember: false,
            ipAddress: '127.0.0.1',
        )
    )
        ->toThrow(
            LoginFailed::class,
            'Die eingegebenen Zugangsdaten sind ungültig.'
        );

    expect(Auth::check())
        ->toBeFalse();
});

it('rejects an unknown email with the same neutral message', function () {
    expect(
        fn () => app(
            AttemptLoginAction::class
        )->execute(
            email: 'unknown@example.test',
            password: 'Sicher123!',
            remember: false,
            ipAddress: '127.0.0.1',
        )
    )
        ->toThrow(
            LoginFailed::class,
            'Die eingegebenen Zugangsdaten sind ungültig.'
        );
});

it('rejects non active account states', function (
    UserStatus $status,
) {
    makeLoginUser([
        'status' => $status,
    ]);

    expect(
        fn () => app(
            AttemptLoginAction::class
        )->execute(
            email: 'erika@example.test',
            password: 'Sicher123!',
            remember: false,
            ipAddress: '127.0.0.1',
        )
    )->toThrow(LoginFailed::class);
})->with([
    UserStatus::PendingVerification,
    UserStatus::Disabled,
    UserStatus::PendingDeletion,
    UserStatus::Anonymized,
]);

it('rejects an unverified active account', function () {
    makeLoginUser([
        'email_verified_at' => null,
    ]);

    expect(
        fn () => app(
            AttemptLoginAction::class
        )->execute(
            email: 'erika@example.test',
            password: 'Sicher123!',
            remember: false,
            ipAddress: '127.0.0.1',
        )
    )->toThrow(LoginFailed::class);
});

it('locks the normalized user key after five failures', function () {
    makeLoginUser();

    $action = app(AttemptLoginAction::class);

    foreach (range(1, 5) as $attempt) {
        try {
            $action->execute(
                email: 'erika@example.test',
                password: 'Falsch123!',
                remember: false,
                ipAddress: '127.0.0.1',
            );
        } catch (LoginFailed) {
        }
    }

    expect(
        app(LoginRateLimiter::class)
            ->tooManyUserAttempts(
                'erika@example.test'
            )
    )->toBeTrue();

    expect(
        fn () => $action->execute(
            email: 'erika@example.test',
            password: 'Sicher123!',
            remember: false,
            ipAddress: '127.0.0.1',
        )
    )->toThrow(
        LoginFailed::class,
        'Die Anmeldung ist vorübergehend nicht möglich. Bitte versuchen Sie es später erneut.'
    );
});

it('clears only the user limiter after successful login', function () {
    makeLoginUser();

    $limiter = app(LoginRateLimiter::class);
    $action = app(AttemptLoginAction::class);

    try {
        $action->execute(
            email: 'erika@example.test',
            password: 'Falsch123!',
            remember: false,
            ipAddress: '127.0.0.1',
        );
    } catch (LoginFailed) {
    }

    $action->execute(
        email: 'erika@example.test',
        password: 'Sicher123!',
        remember: false,
        ipAddress: '127.0.0.1',
    );

    expect(
        $limiter->tooManyUserAttempts(
            'erika@example.test'
        )
    )->toBeFalse();
});

it('locks an ip after twenty five failed logins', function () {
    $action = app(AttemptLoginAction::class);

    foreach (range(1, 25) as $attempt) {
        try {
            $action->execute(
                email:
                    "unknown-{$attempt}@example.test",
                password: 'Falsch123!',
                remember: false,
                ipAddress: '192.0.2.25',
            );
        } catch (LoginFailed) {
        }
    }

    expect(
        app(LoginRateLimiter::class)
            ->tooManyIpAttempts(
                '192.0.2.25'
            )
    )->toBeTrue();
});

it('does not clear the ip limiter after a successful login', function () {
    makeLoginUser();

    $action = app(AttemptLoginAction::class);
    $ip = '192.0.2.26';

    try {
        $action->execute(
            email: 'unknown-before@example.test',
            password: 'Falsch123!',
            remember: false,
            ipAddress: $ip,
        );
    } catch (LoginFailed) {
    }

    $action->execute(
        email: 'erika@example.test',
        password: 'Sicher123!',
        remember: false,
        ipAddress: $ip,
    );

    /*
     * 24 weitere unterschiedliche User-Keys.
     * Zusammen mit dem ersten Fehler vor dem
     * erfolgreichen Login müssen damit 25
     * IP-Fehler erreicht werden.
     */
    foreach (range(1, 24) as $attempt) {
        try {
            $action->execute(
                email:
                    "other-{$attempt}@example.test",
                password: 'Falsch123!',
                remember: false,
                ipAddress: $ip,
            );
        } catch (LoginFailed) {
        }
    }

    expect(
        app(LoginRateLimiter::class)
            ->tooManyIpAttempts($ip)
    )->toBeTrue();
});

it('limits remember me to thirty days', function () {
    makeLoginUser();

    app(AttemptLoginAction::class)->execute(
        email: 'erika@example.test',
        password: 'Sicher123!',
        remember: true,
        ipAddress: '127.0.0.1',
    );

    expect(
        config('auth.remember_duration')
    )->toBe(60 * 24 * 30);
});