<?php

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Actions\Auth\ResetPasswordAction;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Exceptions\PasswordResetFailed;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

function makePasswordResetUser(): User
{
    $user = User::query()->create([
        'email' => 'reset@example.test',
        'password' => 'AltSicher123!',
        'status' => UserStatus::Active,
        'session_version' => 3,
    ]);

    $user->email_verified_at = now();
    $user->remember_token = 'remember-me-token';
    $user->save();

    return $user->refresh();
}

it('resets the password and invalidates all sessions', function () {
    $user = makePasswordResetUser();

    DB::table('sessions')->insert([
        [
            'id' => 'session-one',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Browser One',
            'payload' => 'one',
            'last_activity' => now()->timestamp,
        ],
        [
            'id' => 'session-two',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.2',
            'user_agent' => 'Browser Two',
            'payload' => 'two',
            'last_activity' => now()->timestamp,
        ],
    ]);

    $token = Password::broker()
        ->createToken($user);

    app(ResetPasswordAction::class)->execute(
        email: 'RESET@EXAMPLE.TEST',
        token: $token,
        password: 'NeuSicher456!',
        passwordConfirmation: 'NeuSicher456!',
        ipAddress: '127.0.0.1',
        userAgent: 'Pest Browser',
    );

    $user->refresh();

    expect(
        Hash::check(
            'NeuSicher456!',
            $user->password,
        )
    )
        ->toBeTrue()
        ->and($user->session_version)
        ->toBe(4)
        ->and($user->remember_token)
        ->toBeNull()
        ->and(
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->count()
        )
        ->toBe(0)
        ->and(
            AuditEvent::query()
                ->where(
                    'event_key',
                    AuditEventCatalog::AUTH_PASSWORD_RESET_COMPLETED,
                )
                ->count()
        )
        ->toBe(1)
        ->and(
            AuditEvent::query()
                ->where(
                    'event_key',
                    AuditEventCatalog::AUTH_SESSIONS_INVALIDATED,
                )
                ->count()
        )
        ->toBe(1);
});

it('allows a reset token to be used only once', function () {
    $user = makePasswordResetUser();

    $token = Password::broker()
        ->createToken($user);

    $action = app(ResetPasswordAction::class);

    $action->execute(
        email: $user->email,
        token: $token,
        password: 'NeuSicher456!',
        passwordConfirmation: 'NeuSicher456!',
    );

    expect(
        fn () => $action->execute(
            email: $user->email,
            token: $token,
            password: 'NochNeu789!',
            passwordConfirmation: 'NochNeu789!',
        )
    )->toThrow(
        PasswordResetFailed::class
    );
});

it('rejects an invalid reset token', function () {
    $user = makePasswordResetUser();

    expect(
        fn () => app(
            ResetPasswordAction::class
        )->execute(
            email: $user->email,
            token: 'invalid-token',
            password: 'NeuSicher456!',
            passwordConfirmation: 'NeuSicher456!',
        )
    )->toThrow(
        PasswordResetFailed::class
    );
});
