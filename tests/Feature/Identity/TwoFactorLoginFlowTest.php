<?php

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Actions\Auth\AttemptLoginAction;
use App\Modules\Identity\Actions\Auth\FinalizeLoginAction;
use App\Modules\Identity\Actions\TwoFactor\VerifyEmailTwoFactorChallengeAction;
use App\Modules\Identity\Enums\TwoFactorMethodType;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\TwoFactorEmailChallenge;
use App\Modules\Identity\Models\TwoFactorMethod;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\PendingLogin;
use Illuminate\Support\Facades\Hash;

function makeTwoFactorLoginUser(): User
{
    $user = User::query()->create([
        'email' => 'twofactor-login@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 3,
    ]);

    $user->email_verified_at = now();
    $user->save();

    TwoFactorMethod::query()->create([
        'user_id' => $user->id,
        'type' => TwoFactorMethodType::Email,
        'confirmed_at' => now(),
    ]);

    return $user->refresh();
}

it('does not fully authenticate a two factor user after password only', function () {
    $user = makeTwoFactorLoginUser();

    app(AttemptLoginAction::class)
        ->execute(
            email: $user->email,
            password: 'Sicher123!',
            remember: true,
            ipAddress: '192.0.2.40',
        );

    $user->refresh();

    $this->assertGuest();

    expect(
        app(PendingLogin::class)
            ->user()?->id
    )
        ->toBe($user->id)
        ->and($user->last_login_at)
        ->toBeNull()
        ->and(
            AuditEvent::query()
                ->where(
                    'event_key',
                    AuditEventCatalog::AUTH_LOGIN_SUCCEEDED,
                )
                ->count()
        )
        ->toBe(0);
});

it('fully authenticates only after the second factor succeeds', function () {
    $user = makeTwoFactorLoginUser();

    app(AttemptLoginAction::class)
        ->execute(
            email: $user->email,
            password: 'Sicher123!',
            remember: false,
            ipAddress: '192.0.2.41',
        );

    TwoFactorEmailChallenge::query()
        ->create([
            'user_id' => $user->id,
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(15),
            'sent_at' => now(),
        ]);

    app(
        VerifyEmailTwoFactorChallengeAction::class
    )->execute(
        user: $user,
        code: '123456',
        ipAddress: '192.0.2.41',
    );

    $pending = app(
        PendingLogin::class
    );

    $data = $pending->data();

    expect($data)
        ->not->toBeNull();

    app(FinalizeLoginAction::class)
        ->execute(
            user: $user,
            remember: $data['remember'],
            method: 'password+email_2fa',
            expectedSessionVersion: $data['session_version'],
            ipAddress: '192.0.2.41',
        );

    $this->assertAuthenticatedAs(
        $user
    );

    expect(
        session()->get(
            'identity.two_factor_verified_at'
        )
    )
        ->not->toBeNull()
        ->and(
            app(PendingLogin::class)
                ->exists()
        )
        ->toBeFalse();

    $audit = AuditEvent::query()
        ->where(
            'event_key',
            AuditEventCatalog::AUTH_LOGIN_SUCCEEDED,
        )
        ->sole();

    expect(
        $audit->new_values['method']
    )->toBe(
        'password+email_2fa'
    );
});

it('expires an unfinished pending login', function () {
    $user = makeTwoFactorLoginUser();

    app(AttemptLoginAction::class)
        ->execute(
            email: $user->email,
            password: 'Sicher123!',
            remember: false,
            ipAddress: '192.0.2.42',
        );

    $this->travel(11)->minutes();

    expect(
        app(PendingLogin::class)
            ->user()
    )->toBeNull();

    $this->assertGuest();
});
