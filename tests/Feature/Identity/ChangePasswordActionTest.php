<?php

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Actions\Auth\ChangePasswordAction;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

function makePasswordChangeUser(): User
{
    $user = User::query()->create([
        'email' => 'change@example.test',
        'password' => 'AltSicher123!',
        'status' => UserStatus::Active,
        'session_version' => 7,
    ]);

    $user->email_verified_at = now();
    $user->remember_token = 'existing-remember-token';
    $user->save();

    return $user->refresh();
}

it('changes the password with the current password and audit', function () {
    $user = makePasswordChangeUser();

    app(ChangePasswordAction::class)->execute(
        user: $user,
        currentPassword: 'AltSicher123!',
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
        ->toBe(7)
        ->and($user->remember_token)
        ->toBe('existing-remember-token')
        ->and(
            AuditEvent::query()
                ->where(
                    'event_key',
                    AuditEventCatalog::AUTH_PASSWORD_CHANGED,
                )
                ->count()
        )
        ->toBe(1);
});

it('requires the correct current password', function () {
    $user = makePasswordChangeUser();

    expect(
        fn () => app(
            ChangePasswordAction::class
        )->execute(
            user: $user,
            currentPassword: 'Falsch123!',
            password: 'NeuSicher456!',
            passwordConfirmation: 'NeuSicher456!',
        )
    )->toThrow(ValidationException::class);

    $user->refresh();

    expect(
        Hash::check(
            'AltSicher123!',
            $user->password,
        )
    )->toBeTrue();
});

it('applies the password policy', function () {
    $user = makePasswordChangeUser();

    expect(
        fn () => app(
            ChangePasswordAction::class
        )->execute(
            user: $user,
            currentPassword: 'AltSicher123!',
            password: 'weak',
            passwordConfirmation: 'weak',
        )
    )->toThrow(ValidationException::class);
});

it('keeps other sessions after a normal password change', function () {
    $user = makePasswordChangeUser();

    DB::table('sessions')->insert([
        'id' => 'other-session',
        'user_id' => $user->id,
        'ip_address' => '127.0.0.2',
        'user_agent' => 'Other Browser',
        'payload' => 'test',
        'last_activity' => now()->timestamp,
    ]);

    app(ChangePasswordAction::class)->execute(
        user: $user,
        currentPassword: 'AltSicher123!',
        password: 'NeuSicher456!',
        passwordConfirmation: 'NeuSicher456!',
    );

    expect(
        DB::table('sessions')
            ->where('id', 'other-session')
            ->exists()
    )->toBeTrue();
});
