<?php

use App\Modules\Access\Mail\PasswordResetMail;
use App\Modules\Access\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->withoutVite();
});

/** @return array{token: string, email: string} Token und Adresse aus dem gesendeten Link */
function prRequestLink($test, string $email = 'erika@example.org'): array
{
    $test->post(route('password.email'), ['email' => $email]);

    $mail = Mail::sent(PasswordResetMail::class)->first();
    preg_match('~/passwort-zuruecksetzen/([^?]+)\?email=(.+)$~', $mail->url, $parts);

    return ['token' => $parts[1], 'email' => urldecode($parts[2])];
}

it('shows the request page and links to it from the login', function () {
    $this->get(route('password.request'))->assertOk()->assertSee('Passwort vergessen');
    $this->get(route('login'))->assertOk()->assertSee(route('password.request'), false);
});

it('sends the reset link to an active account', function () {
    Mail::fake();
    User::factory()->create(['email' => 'erika@example.org']);

    $this->post(route('password.email'), ['email' => 'erika@example.org'])
        ->assertRedirect()
        ->assertSessionHas('success');

    Mail::assertSent(PasswordResetMail::class, fn (PasswordResetMail $mail): bool => $mail->hasTo('erika@example.org') && str_contains($mail->url, '/passwort-zuruecksetzen/'));
});

it('gives the same answer for unknown and inactive accounts and sends nothing', function () {
    Mail::fake();
    User::factory()->unverified()->create(['email' => 'neu@example.org']);

    $known = $this->post(route('password.email'), ['email' => 'neu@example.org']);
    $unknown = $this->post(route('password.email'), ['email' => 'niemand@example.org']);

    expect(session('success'))->not->toBeNull();
    $known->assertSessionHas('success');
    $unknown->assertSessionHas('success');
    Mail::assertNothingSent();
});

it('validates the email address when requesting a link', function () {
    $this->post(route('password.email'), ['email' => 'kein-mail'])->assertSessionHasErrors('email');
    $this->post(route('password.email'), [])->assertSessionHasErrors('email');
});

it('sets a new password with the link, ends old sessions and consumes the token', function () {
    Mail::fake();
    $user = User::factory()->create(['email' => 'erika@example.org']);
    $link = prRequestLink($this);

    $this->get(route('password.reset', ['token' => $link['token'], 'email' => $link['email']]))->assertOk()->assertSee('Neues Passwort');

    $this->post(route('password.update'), $link + ['password' => 'Neues-Passwort-2026', 'password_confirmation' => 'Neues-Passwort-2026'])
        ->assertRedirect(route('login'))
        ->assertSessionHas('success');

    $fresh = $user->fresh();
    expect(Hash::check('Neues-Passwort-2026', $fresh->password))->toBeTrue()
        ->and($fresh->session_version)->toBe(2);

    $this->post(route('password.update'), $link + ['password' => 'Anderes-Passwort-2026', 'password_confirmation' => 'Anderes-Passwort-2026'])
        ->assertSessionHasErrors('email');
});

it('lets the user log in with the new password', function () {
    Mail::fake();
    User::factory()->create(['email' => 'erika@example.org']);
    $link = prRequestLink($this);

    $this->post(route('password.update'), $link + ['password' => 'Neues-Passwort-2026', 'password_confirmation' => 'Neues-Passwort-2026']);

    $this->post('/login', ['email' => 'erika@example.org', 'password' => 'password'])->assertSessionHasErrors('email');
    $this->post('/login', ['email' => 'erika@example.org', 'password' => 'Neues-Passwort-2026'])->assertRedirect(route('home'));
});

it('rejects weak passwords and mismatching confirmations', function () {
    Mail::fake();
    $user = User::factory()->create(['email' => 'erika@example.org']);
    $link = prRequestLink($this);

    foreach (['kurz1A', 'nurkleinbuchstaben123', 'NURGROSSBUCHSTABEN123', 'KeineZiffernImPasswort'] as $weak) {
        $this->post(route('password.update'), $link + ['password' => $weak, 'password_confirmation' => $weak])->assertSessionHasErrors('password');
    }

    $this->post(route('password.update'), $link + ['password' => 'Neues-Passwort-2026', 'password_confirmation' => 'Etwas-Anderes-2026'])->assertSessionHasErrors('password');

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

it('rejects an invalid token', function () {
    User::factory()->create(['email' => 'erika@example.org']);

    $this->post(route('password.update'), ['token' => 'falsch', 'email' => 'erika@example.org', 'password' => 'Neues-Passwort-2026', 'password_confirmation' => 'Neues-Passwort-2026'])
        ->assertSessionHasErrors('email');
});

it('rejects an expired link', function () {
    Mail::fake();
    User::factory()->create(['email' => 'erika@example.org']);
    $link = prRequestLink($this);

    $this->travel(61)->minutes();

    $this->post(route('password.update'), $link + ['password' => 'Neues-Passwort-2026', 'password_confirmation' => 'Neues-Passwort-2026'])
        ->assertSessionHasErrors('email');
});
