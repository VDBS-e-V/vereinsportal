<?php

use App\Modules\Access\Enums\TwoFactorMethodType;
use App\Modules\Access\Mail\TwoFactorCodeMail;
use App\Modules\Access\Models\User;
use App\Modules\Access\Services\RecoveryCodes;
use App\Modules\Access\Services\Totp;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->withoutVite();
});

function secUser(): User
{
    return User::factory()->create(['email' => 'erika@example.org']);
}

function secWithTotp(User $user): string
{
    $secret = Totp::generateSecret();
    $user->twoFactorMethods()->create(['type' => TwoFactorMethodType::Totp, 'secret' => $secret, 'confirmed_at' => now()]);
    $user->forceFill(['preferred_two_factor_method' => TwoFactorMethodType::Totp])->save();

    return $secret;
}

it('sends guests to the login for every security page', function () {
    foreach (['account.security.index', 'account.security.totp', 'account.security.email', 'account.security.recovery'] as $route) {
        $this->get(route($route))->assertRedirect(route('login'));
    }
});

it('shows the security overview with a warning while nothing is set up', function () {
    $this->actingAs(secUser())
        ->get(route('account.security.index'))
        ->assertOk()
        ->assertSee('Nicht eingerichtet')
        ->assertSee('noch nicht durch einen zweiten Faktor geschützt')
        ->assertDontSee('Wiederherstellungscodes erzeugen');
});

it('sets up the authenticator app with a confirmation code and shows the recovery codes once', function () {
    $user = secUser();
    $this->actingAs($user);

    $this->get(route('account.security.totp'))->assertOk()->assertSee('Authenticator-App einrichten');

    $pending = $user->twoFactorMethods()->first();
    expect($pending->confirmed_at)->toBeNull()
        ->and($pending->secret)->toMatch('/^[A-Z2-7]{32}$/');

    $this->post(route('account.security.totp.store'), ['code' => '000000'])->assertSessionHasErrors('code');
    expect($pending->fresh()->confirmed_at)->toBeNull();

    $this->post(route('account.security.totp.store'), ['code' => Totp::code($pending->secret)])
        ->assertRedirect(route('account.security.recovery'));

    expect($pending->fresh()->confirmed_at)->not->toBeNull()
        ->and($user->fresh()->preferred_two_factor_method)->toBe(TwoFactorMethodType::Totp)
        ->and(app(RecoveryCodes::class)->remaining($user))->toBe(10);

    $codes = session('recovery_codes');
    expect($codes)->toHaveCount(10);

    $page = $this->get(route('account.security.recovery'))->assertOk()->assertSee('Diese Codes sehen Sie nur jetzt');

    foreach ($codes as $code) {
        $page->assertSee($code);
    }

    $this->get(route('account.security.recovery'))->assertRedirect(route('account.security.index'));
});

it('reuses the pending secret when the setup page is opened again', function () {
    $user = secUser();
    $this->actingAs($user);

    $this->get(route('account.security.totp'));
    $first = $user->twoFactorMethods()->first()->secret;
    $this->get(route('account.security.totp'));

    expect($user->twoFactorMethods()->count())->toBe(1)
        ->and($user->twoFactorMethods()->first()->secret)->toBe($first);
});

it('does not offer the app setup again once it is active', function () {
    $user = secUser();
    secWithTotp($user);

    $this->actingAs($user)->get(route('account.security.totp'))->assertRedirect(route('account.security.index'));
});

it('sets up the email code with the code from the mail', function () {
    Mail::fake();
    $user = secUser();
    $this->actingAs($user);

    $this->get(route('account.security.email'))->assertOk()->assertSee('erika@example.org');
    Mail::assertSent(TwoFactorCodeMail::class, 1);

    $this->post(route('account.security.email.store'), ['code' => '000000'])->assertSessionHasErrors('code');
    expect($user->twoFactorMethods()->whereNotNull('confirmed_at')->count())->toBe(0);

    $code = Mail::sent(TwoFactorCodeMail::class)->first()->code;
    $this->post(route('account.security.email.store'), ['code' => $code])->assertRedirect(route('account.security.recovery'));

    expect($user->twoFactorMethods()->where('type', 'email')->whereNotNull('confirmed_at')->exists())->toBeTrue()
        ->and(app(RecoveryCodes::class)->remaining($user))->toBe(10);
});

it('keeps the existing recovery codes when a second method is added', function () {
    Mail::fake();
    $user = secUser();
    secWithTotp($user);
    app(RecoveryCodes::class)->generate($user);
    $this->actingAs($user);

    $this->get(route('account.security.email'));
    $code = Mail::sent(TwoFactorCodeMail::class)->first()->code;

    $this->post(route('account.security.email.store'), ['code' => $code])->assertRedirect(route('account.security.index'));

    expect(app(RecoveryCodes::class)->remaining($user))->toBe(10);
});

it('requires the current password to remove a method', function () {
    $user = secUser();
    secWithTotp($user);
    $this->actingAs($user);

    $this->delete(route('account.security.destroy', 'totp'), ['password' => 'falsch'])->assertSessionHasErrors('password');
    $this->delete(route('account.security.destroy', 'totp'))->assertSessionHasErrors('password');
    expect($user->twoFactorMethods()->whereNull('disabled_at')->count())->toBe(1);

    $this->delete(route('account.security.destroy', 'totp'), ['password' => 'password'])->assertRedirect(route('account.security.index'));

    expect($user->twoFactorMethods()->whereNull('disabled_at')->count())->toBe(0)
        ->and($user->fresh()->preferred_two_factor_method)->toBeNull();
});

it('invalidates the recovery codes with the last method but keeps them with another one', function () {
    $user = secUser();
    secWithTotp($user);
    $user->twoFactorMethods()->create(['type' => TwoFactorMethodType::Email, 'confirmed_at' => now()]);
    app(RecoveryCodes::class)->generate($user);
    $this->actingAs($user);

    $this->delete(route('account.security.destroy', 'totp'), ['password' => 'password']);
    expect(app(RecoveryCodes::class)->remaining($user))->toBe(10)
        ->and($user->fresh()->preferred_two_factor_method)->toBe(TwoFactorMethodType::Email);

    $this->delete(route('account.security.destroy', 'email'), ['password' => 'password']);
    expect(app(RecoveryCodes::class)->remaining($user))->toBe(0);
});

it('ignores unknown method types when removing', function () {
    $this->actingAs(secUser())->delete('/konto/sicherheit/sms', ['password' => 'password'])->assertNotFound();
});

it('replaces the recovery codes only with the password and only with an active method', function () {
    $user = secUser();
    $this->actingAs($user);

    $this->post(route('account.security.recovery.store'), ['password' => 'password'])->assertRedirect(route('account.security.index'));
    expect(app(RecoveryCodes::class)->remaining($user))->toBe(0);

    secWithTotp($user);
    $old = app(RecoveryCodes::class)->generate($user);

    $this->post(route('account.security.recovery.store'), ['password' => 'falsch'])->assertSessionHasErrors('password');
    expect(app(RecoveryCodes::class)->consume($user, $old[0]))->toBeTrue();

    $this->post(route('account.security.recovery.store'), ['password' => 'password'])->assertRedirect(route('account.security.recovery'));

    expect(app(RecoveryCodes::class)->consume($user, $old[1]))->toBeFalse()
        ->and(app(RecoveryCodes::class)->remaining($user))->toBe(10);
});

it('shows the state of the methods on the overview', function () {
    $user = secUser();
    secWithTotp($user);
    app(RecoveryCodes::class)->generate($user);

    $this->actingAs($user)
        ->get(route('account.security.index'))
        ->assertOk()
        ->assertSee('Zwei-Faktor aktiv')
        ->assertSee('von 10 Codes')
        ->assertSee('remove-totp', false)
        ->assertSee('Neue Codes erzeugen');
});

it('stores recovery codes only as hashes and normalizes the input', function () {
    $user = secUser();
    $codes = app(RecoveryCodes::class)->generate($user);

    expect($codes)->toHaveCount(10)
        ->and($codes[0])->toMatch('/^[A-HJKMNP-Z2-9]{5}-[A-HJKMNP-Z2-9]{5}$/')
        ->and($user->twoFactorRecoveryCodes()->pluck('code_hash')->contains($codes[0]))->toBeFalse()
        ->and(app(RecoveryCodes::class)->consume($user, ' '.strtolower(str_replace('-', ' ', $codes[2])).' '))->toBeTrue()
        ->and(app(RecoveryCodes::class)->consume($user, $codes[2]))->toBeFalse()
        ->and(app(RecoveryCodes::class)->consume($user, ''))->toBeFalse();
});
