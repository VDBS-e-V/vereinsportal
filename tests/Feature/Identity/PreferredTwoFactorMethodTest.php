<?php

use App\Modules\Identity\Actions\TwoFactor\DisableTwoFactorMethodAction;
use App\Modules\Identity\Actions\TwoFactor\SetPreferredTwoFactorMethodAction;
use App\Modules\Identity\Actions\TwoFactor\VerifyEmailTwoFactorChallengeAction;
use App\Modules\Identity\Enums\TwoFactorMethodType;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Exceptions\TwoFactorChallengeFailed;
use App\Modules\Identity\Exceptions\TwoFactorSetupFailed;
use App\Modules\Identity\Models\TwoFactorEmailChallenge;
use App\Modules\Identity\Models\TwoFactorMethod;
use App\Modules\Identity\Models\TwoFactorRecoveryCode;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\PendingLogin;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;

function makePreferredTwoFactorUser(): User
{
    $user = User::query()->create([
        'email' => fake()->unique()->safeEmail(),
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 1,
    ]);

    $user->email_verified_at = now();
    $user->save();

    return $user->refresh();
}

function addPreferredTwoFactorMethod(
    User $user,
    TwoFactorMethodType $type,
): TwoFactorMethod {
    return TwoFactorMethod::query()->create([
        'user_id' => $user->id,
        'type' => $type,
        'secret' => $type === TwoFactorMethodType::Totp
            ? str_repeat('A', 32)
            : null,
        'confirmed_at' => now(),
    ]);
}

function startPreferredTwoFactorLogin(User $user): void
{
    app(PendingLogin::class)->start(
        user: $user,
        remember: false,
    );
}

it('shows only the preferred method in the normal two factor challenge', function () {
    $user = makePreferredTwoFactorUser();
    addPreferredTwoFactorMethod($user, TwoFactorMethodType::Email);
    addPreferredTwoFactorMethod($user, TwoFactorMethodType::Totp);

    $user->preferred_two_factor_method = TwoFactorMethodType::Email;
    $user->save();

    startPreferredTwoFactorLogin($user);

    $this
        ->get('http://my.vdb.test/anmeldung/2fa')
        ->assertOk()
        ->assertSee('E-Mail-Code')
        ->assertDontSee('Authenticator-App')
        ->assertSee('Andere 2FA-Methode verwenden');
});

it('allows selecting another available method for the pending login', function () {
    $user = makePreferredTwoFactorUser();
    addPreferredTwoFactorMethod($user, TwoFactorMethodType::Email);
    addPreferredTwoFactorMethod($user, TwoFactorMethodType::Totp);

    $user->preferred_two_factor_method = TwoFactorMethodType::Email;
    $user->save();

    startPreferredTwoFactorLogin($user);

    $this
        ->get('http://my.vdb.test/anmeldung/2fa?method=totp')
        ->assertOk()
        ->assertSee('Authenticator-App')
        ->assertDontSee('E-Mail-Code');
});

it('lists all available methods without exposing unavailable methods', function () {
    $user = makePreferredTwoFactorUser();
    addPreferredTwoFactorMethod($user, TwoFactorMethodType::Email);

    $user->preferred_two_factor_method = TwoFactorMethodType::Email;
    $user->save();

    TwoFactorRecoveryCode::query()->create([
        'user_id' => $user->id,
        'code_hash' => Hash::make('recovery-code'),
    ]);

    startPreferredTwoFactorLogin($user);

    $this
        ->get('http://my.vdb.test/anmeldung/2fa/methode')
        ->assertOk()
        ->assertSee('E-Mail-Code')
        ->assertSee('Recovery Code')
        ->assertSee('Bevorzugt')
        ->assertDontSee('Authenticator-App');
});

it('rejects unavailable preferences and persists an available preference', function () {
    $user = makePreferredTwoFactorUser();
    addPreferredTwoFactorMethod($user, TwoFactorMethodType::Email);

    $action = app(SetPreferredTwoFactorMethodAction::class);

    expect(
        fn () => $action->execute(
            user: $user,
            type: TwoFactorMethodType::Totp,
        ),
    )->toThrow(TwoFactorSetupFailed::class);

    $action->execute(
        user: $user,
        type: TwoFactorMethodType::Email,
    );

    expect($user->refresh()->preferred_two_factor_method)
        ->toBe(TwoFactorMethodType::Email);
});

it('clears a preference when its optional method is disabled', function () {
    $user = makePreferredTwoFactorUser();
    addPreferredTwoFactorMethod($user, TwoFactorMethodType::Email);

    app(SetPreferredTwoFactorMethodAction::class)->execute(
        user: $user,
        type: TwoFactorMethodType::Email,
    );

    app(DisableTwoFactorMethodAction::class)->execute(
        user: $user,
        type: TwoFactorMethodType::Email,
    );

    expect($user->refresh()->preferred_two_factor_method)->toBeNull();
});

it('rejects an outstanding email code after email two factor is disabled', function () {
    $user = makePreferredTwoFactorUser();
    addPreferredTwoFactorMethod($user, TwoFactorMethodType::Email);

    TwoFactorEmailChallenge::query()->create([
        'user_id' => $user->id,
        'code_hash' => Hash::make('123456'),
        'expires_at' => now()->addMinutes(15),
        'sent_at' => now(),
    ]);

    app(DisableTwoFactorMethodAction::class)->execute(
        user: $user,
        type: TwoFactorMethodType::Email,
    );

    expect(
        fn () => app(VerifyEmailTwoFactorChallengeAction::class)->execute(
            user: $user,
            code: '123456',
            ipAddress: '192.0.2.60',
        ),
    )->toThrow(TwoFactorChallengeFailed::class);
});

it('stores the successfully used primary method as the next preference', function () {
    $user = makePreferredTwoFactorUser();
    addPreferredTwoFactorMethod($user, TwoFactorMethodType::Email);

    startPreferredTwoFactorLogin($user);

    TwoFactorEmailChallenge::query()->create([
        'user_id' => $user->id,
        'code_hash' => Hash::make('123456'),
        'expires_at' => now()->addMinutes(15),
        'sent_at' => now(),
    ]);

    Volt::test('identity.two-factor-challenge')
        ->set('emailCode', '123456')
        ->call('verifyEmail')
        ->assertHasNoErrors()
        ->assertRedirect(route('my.home'));

    $this->assertAuthenticatedAs($user);

    expect($user->refresh()->preferred_two_factor_method)
        ->toBe(TwoFactorMethodType::Email);
});

it('protects the method selection page without a pending login', function () {
    $this
        ->get('http://my.vdb.test/anmeldung/2fa/methode')
        ->assertRedirect(route('my.login'));
});
