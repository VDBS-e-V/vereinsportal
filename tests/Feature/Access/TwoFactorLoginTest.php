<?php

use App\Modules\Access\Enums\TwoFactorMethodType;
use App\Modules\Access\Mail\TwoFactorCodeMail;
use App\Modules\Access\Models\User;
use App\Modules\Access\Services\EmailChallenges;
use App\Modules\Access\Services\RecoveryCodes;
use App\Modules\Access\Services\Totp;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->withoutVite();
});

/** @return array{0: User, 1: string} Nutzer mit bestätigter Authenticator-App und deren Secret */
function tfUserWithTotp(): array
{
    $user = User::factory()->create(['email' => 'erika@example.org']);
    $secret = Totp::generateSecret();

    $user->twoFactorMethods()->create(['type' => TwoFactorMethodType::Totp, 'secret' => $secret, 'confirmed_at' => now()]);
    $user->forceFill(['preferred_two_factor_method' => TwoFactorMethodType::Totp])->save();

    return [$user, $secret];
}

function tfLogin($test, string $email = 'erika@example.org')
{
    return $test->post('/login', ['email' => $email, 'password' => 'password']);
}

it('asks for the second factor instead of logging in', function () {
    [$user] = tfUserWithTotp();

    tfLogin($this)->assertRedirect(route('two-factor.challenge'));

    $this->assertGuest();
    expect(session('two_factor.user_id'))->toBe($user->id);

    $this->get(route('two-factor.challenge'))->assertOk()->assertSee('Sicherheitscode');
});

it('completes the login with a valid app code', function () {
    [$user, $secret] = tfUserWithTotp();

    tfLogin($this);
    $this->post(route('two-factor.verify'), ['method' => 'totp', 'otp' => Totp::code($secret)])
        ->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->last_login_at)->not->toBeNull()
        ->and(session('two_factor'))->toBeNull();
});

it('rejects a wrong code and stays logged out', function () {
    tfUserWithTotp();

    tfLogin($this);
    $this->post(route('two-factor.verify'), ['method' => 'totp', 'otp' => '000000'])
        ->assertRedirect(route('two-factor.challenge', ['method' => 'totp']))
        ->assertSessionHasErrors('otp');

    $this->assertGuest();
});

it('accepts an app code only once', function () {
    [$user, $secret] = tfUserWithTotp();
    $code = Totp::code($secret);

    tfLogin($this);
    $this->post(route('two-factor.verify'), ['method' => 'totp', 'otp' => $code])->assertRedirect(route('home'));
    $this->post('/logout');

    tfLogin($this);
    $this->post(route('two-factor.verify'), ['method' => 'totp', 'otp' => $code])->assertSessionHasErrors('otp');

    $this->assertGuest();
});

it('locks the code entry after too many wrong attempts', function () {
    [$user, $secret] = tfUserWithTotp();

    tfLogin($this);

    foreach (range(1, 5) as $i) {
        $this->post(route('two-factor.verify'), ['method' => 'totp', 'otp' => '000000']);
    }

    $this->post(route('two-factor.verify'), ['method' => 'totp', 'otp' => Totp::code($secret)])->assertSessionHasErrors('otp');

    $this->assertGuest();
});

it('logs in with a recovery code that works only once', function () {
    [$user] = tfUserWithTotp();
    $codes = app(RecoveryCodes::class)->generate($user);

    tfLogin($this);
    $this->post(route('two-factor.verify'), ['method' => 'recovery', 'otp' => strtolower($codes[0])])->assertRedirect(route('home'));
    $this->assertAuthenticatedAs($user);
    $this->post('/logout');

    tfLogin($this);
    $this->post(route('two-factor.verify'), ['method' => 'recovery', 'otp' => $codes[0]])->assertSessionHasErrors('otp');
    $this->assertGuest();

    expect(app(RecoveryCodes::class)->remaining($user))->toBe(9);
});

it('sends an email code at login when email is the only method and accepts it', function () {
    Mail::fake();
    $user = User::factory()->create(['email' => 'erika@example.org']);
    $user->twoFactorMethods()->create(['type' => TwoFactorMethodType::Email, 'confirmed_at' => now()]);

    tfLogin($this)->assertRedirect(route('two-factor.challenge'));

    Mail::assertSent(TwoFactorCodeMail::class, 1);
    $code = Mail::sent(TwoFactorCodeMail::class)->first()->code;

    expect($code)->toMatch('/^\d{6}$/');

    $this->post(route('two-factor.verify'), ['method' => 'email', 'otp' => $code])->assertRedirect(route('home'));
    $this->assertAuthenticatedAs($user);
});

it('stores only a hash of the email code and invalidates it after use', function () {
    Mail::fake();
    $user = User::factory()->create();
    $user->twoFactorMethods()->create(['type' => TwoFactorMethodType::Email, 'confirmed_at' => now()]);

    app(EmailChallenges::class)->send($user);
    $code = Mail::sent(TwoFactorCodeMail::class)->first()->code;
    $challenge = $user->twoFactorEmailChallenges()->first();

    expect($challenge->code_hash)->not->toContain($code);
    expect(app(EmailChallenges::class)->verify($user, $code))->toBeTrue()
        ->and(app(EmailChallenges::class)->verify($user, $code))->toBeFalse();
});

it('rejects an expired email code', function () {
    Mail::fake();
    $user = User::factory()->create();

    app(EmailChallenges::class)->send($user);
    $code = Mail::sent(TwoFactorCodeMail::class)->first()->code;

    $this->travel(11)->minutes();

    expect(app(EmailChallenges::class)->verify($user, $code))->toBeFalse();
});

it('limits resending the email code to once a minute and replaces the previous code', function () {
    Mail::fake();
    $user = User::factory()->create();
    $challenges = app(EmailChallenges::class);

    expect($challenges->send($user))->toBeTrue()
        ->and($challenges->send($user))->toBeFalse()
        ->and($challenges->secondsUntilResend($user))->toBeGreaterThan(0);
    Mail::assertSent(TwoFactorCodeMail::class, 1);

    $first = Mail::sent(TwoFactorCodeMail::class)->first()->code;
    $this->travel(61)->seconds();

    expect($challenges->send($user))->toBeTrue()
        ->and($challenges->verify($user, $first))->toBeFalse();
    Mail::assertSent(TwoFactorCodeMail::class, 2);
});

it('sends a new email code from the challenge page with a throttle', function () {
    Mail::fake();
    $user = User::factory()->create(['email' => 'erika@example.org']);
    $user->twoFactorMethods()->create(['type' => TwoFactorMethodType::Email, 'confirmed_at' => now()]);

    tfLogin($this);
    $this->post(route('two-factor.resend'))->assertRedirect(route('two-factor.challenge', ['method' => 'email']))->assertSessionHas('warning');

    Mail::assertSent(TwoFactorCodeMail::class, 1);
});

it('sends users without a pending login back to the login page', function () {
    $this->get(route('two-factor.challenge'))->assertRedirect(route('login'));
    $this->post(route('two-factor.verify'), ['method' => 'totp', 'otp' => '123456'])->assertRedirect(route('login'));
});

it('lets the pending login expire after ten minutes', function () {
    tfUserWithTotp();

    tfLogin($this);
    $this->travel(11)->minutes();

    $this->get(route('two-factor.challenge'))->assertRedirect(route('login'));
});

it('still logs in users without a second factor directly', function () {
    $user = User::factory()->create(['email' => 'erika@example.org']);

    tfLogin($this)->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
});

it('does not treat a disabled or unconfirmed method as a second factor', function () {
    $user = User::factory()->create(['email' => 'erika@example.org']);
    $user->twoFactorMethods()->create(['type' => TwoFactorMethodType::Totp, 'secret' => Totp::generateSecret()]);
    $user->twoFactorMethods()->create(['type' => TwoFactorMethodType::Email, 'confirmed_at' => now()->subDay(), 'disabled_at' => now()]);

    tfLogin($this)->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
});
