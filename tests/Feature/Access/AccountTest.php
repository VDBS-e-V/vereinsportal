<?php

use App\Modules\Access\Enums\EmailChangeRequestStatus;
use App\Modules\Access\Http\Controllers\SessionsController;
use App\Modules\Access\Mail\EmailChangeConfirmMail;
use App\Modules\Access\Mail\EmailChangeNoticeMail;
use App\Modules\Access\Models\User;
use App\Modules\People\Models\Person;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->withoutVite();
});

function accUser(array $person = []): User
{
    $personModel = Person::factory()->create(['first_name' => 'Erika', 'last_name' => 'Mustermann', 'email' => 'erika@example.org', 'birth_date' => '1980-03-12'] + $person);

    return User::factory()->create(['email' => 'erika@example.org', 'person_id' => $personModel->id]);
}

it('sends guests to the login for all account pages', function () {
    foreach (['account.profile', 'account.email', 'account.password', 'account.sessions', 'account.security.index'] as $route) {
        $this->get(route($route))->assertRedirect(route('login'));
    }

    $this->get('/konto')->assertRedirect(route('login'));
});

it('redirects the account root to the profile', function () {
    $this->actingAs(accUser())->get('/konto')->assertRedirect('/konto/profil');
});

it('shows the account navigation on every account page', function () {
    $this->actingAs(accUser());

    foreach (['account.profile', 'account.email', 'account.password', 'account.sessions', 'account.security.index'] as $route) {
        $this->get(route($route))
            ->assertOk()
            ->assertSee('aria-label="Seiten im Bereich Konto"', false)
            ->assertSee(route('account.password'), false)
            ->assertSee(route('account.sessions'), false);
    }
});

// ---------- Profil ----------

it('shows the master data of the person', function () {
    $this->actingAs(accUser())
        ->get(route('account.profile'))
        ->assertOk()
        ->assertSee('Erika Mustermann')
        ->assertSee('12.03.1980')
        ->assertSee('erika@example.org');
});

it('lets the member change phone and address but not the name', function () {
    $user = accUser();

    $this->actingAs($user)
        ->put(route('account.profile.update'), [
            'phone' => '030 123456', 'street' => 'Hauptstraße', 'house_number' => '5a', 'postal_code' => '10115', 'city' => 'Berlin', 'country_code' => 'de',
            'first_name' => 'Hacker', 'birth_date' => '1990-01-01',
        ])
        ->assertRedirect(route('account.profile'))
        ->assertSessionHas('success');

    $person = $user->person->fresh();
    expect($person->phone)->toBe('030 123456')
        ->and($person->city)->toBe('Berlin')
        ->and($person->country_code)->toBe('DE')
        ->and($person->first_name)->toBe('Erika')
        ->and($person->birth_date->format('Y-m-d'))->toBe('1980-03-12');
});

it('validates the country code of the profile', function () {
    $this->actingAs(accUser())->put(route('account.profile.update'), ['country_code' => 'DEU'])->assertSessionHasErrors('country_code');
});

it('explains that an account without a person has no master data', function () {
    $this->actingAs(User::factory()->create(['email' => 'allein@example.org']))
        ->get(route('account.profile'))
        ->assertOk()
        ->assertSee('keiner Person zugeordnet');
});

// ---------- Passwort ----------

it('changes the password with the current one and ends other sessions', function () {
    $user = accUser();

    $this->actingAs($user)
        ->put(route('account.password.update'), ['current_password' => 'password', 'password' => 'Neues-Passwort-2026', 'password_confirmation' => 'Neues-Passwort-2026'])
        ->assertRedirect(route('account.password'))
        ->assertSessionHas('success');

    expect(Hash::check('Neues-Passwort-2026', $user->fresh()->password))->toBeTrue()
        ->and($user->fresh()->session_version)->toBe(2);

    $this->assertAuthenticatedAs($user);
    $this->get(route('account.password'))->assertOk();
});

it('refuses a wrong current password, a weak or an unchanged new password', function () {
    $user = accUser();
    $this->actingAs($user);

    $this->put(route('account.password.update'), ['current_password' => 'falsch', 'password' => 'Neues-Passwort-2026', 'password_confirmation' => 'Neues-Passwort-2026'])->assertSessionHasErrors('current_password');
    $this->put(route('account.password.update'), ['current_password' => 'password', 'password' => 'kurz', 'password_confirmation' => 'kurz'])->assertSessionHasErrors('password');
    $this->put(route('account.password.update'), ['current_password' => 'password', 'password' => 'Neues-Passwort-2026', 'password_confirmation' => 'anders'])->assertSessionHasErrors('password');

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

// ---------- Sitzungsversion ----------

it('ends a session whose version no longer matches the account', function () {
    $user = accUser();

    $this->actingAs($user)->get(route('account.profile'))->assertOk();

    $user->forceFill(['session_version' => $user->session_version + 1])->save();

    $this->get(route('account.profile'))->assertRedirect(route('login'));
    $this->assertGuest();
});

it('adopts the current version in sessions that have none yet', function () {
    $user = accUser();
    $user->forceFill(['session_version' => 7])->save();

    $this->actingAs($user)->get(route('account.profile'))->assertOk();

    expect(session('session_version'))->toBe(7);
});

it('remembers the version at login', function () {
    $user = User::factory()->create(['email' => 'erika@example.org']);

    $this->post('/login', ['email' => 'erika@example.org', 'password' => 'password']);

    expect(session('session_version'))->toBe($user->session_version);
});

// ---------- E-Mail-Adresse ----------

it('sends a confirmation link to the new address and a notice to the old one', function () {
    Mail::fake();
    $user = accUser();

    $this->actingAs($user)
        ->post(route('account.email.store'), ['new_email' => 'Neu@Example.org', 'password' => 'password'])
        ->assertRedirect(route('account.email'))
        ->assertSessionHas('success');

    Mail::assertSent(EmailChangeConfirmMail::class, fn (EmailChangeConfirmMail $mail): bool => $mail->hasTo('neu@example.org') && str_contains($mail->url, 'signature='));
    Mail::assertSent(EmailChangeNoticeMail::class, fn (EmailChangeNoticeMail $mail): bool => $mail->hasTo('erika@example.org'));

    expect($user->fresh()->email)->toBe('erika@example.org');

    $request = $user->emailChangeRequests()->first();
    expect($request->status)->toBe(EmailChangeRequestStatus::Pending)
        ->and($request->new_email)->toBe('neu@example.org')
        ->and($request->public_id)->toHaveLength(26);

    $this->get(route('account.email'))->assertOk()->assertSee('neu@example.org')->assertSee('noch nicht bestätigt');
});

it('changes the address only after the confirmation link and updates user and person', function () {
    Mail::fake();
    $user = accUser();
    $this->actingAs($user)->post(route('account.email.store'), ['new_email' => 'neu@example.org', 'password' => 'password']);
    $url = Mail::sent(EmailChangeConfirmMail::class)->first()->url;

    $this->get($url)->assertRedirect(route('account.email'))->assertSessionHas('success');

    $fresh = $user->fresh();
    expect($fresh->email)->toBe('neu@example.org')
        ->and($fresh->person->email)->toBe('neu@example.org')
        ->and($fresh->email_verified_at)->not->toBeNull()
        ->and($fresh->session_version)->toBe(2)
        ->and($fresh->emailChangeRequests()->first()->status)->toBe(EmailChangeRequestStatus::Confirmed);

    $this->get($url)->assertRedirect(route('account.email'))->assertSessionHas('warning');
});

it('rejects tampered and expired confirmation links', function () {
    Mail::fake();
    $user = accUser();
    $this->actingAs($user)->post(route('account.email.store'), ['new_email' => 'neu@example.org', 'password' => 'password']);
    $url = Mail::sent(EmailChangeConfirmMail::class)->first()->url;

    $this->get($url.'x')->assertForbidden();

    $this->travel(25)->hours();
    $this->get($url)->assertForbidden();

    expect($user->fresh()->email)->toBe('erika@example.org');
});

it('does not let another account confirm the request', function () {
    Mail::fake();
    $owner = accUser();
    $this->actingAs($owner)->post(route('account.email.store'), ['new_email' => 'neu@example.org', 'password' => 'password']);
    $url = Mail::sent(EmailChangeConfirmMail::class)->first()->url;

    $other = User::factory()->create(['email' => 'andere@example.org']);
    $this->actingAs($other)->get($url)->assertRedirect(route('account.email'))->assertSessionHas('warning');

    expect($owner->fresh()->email)->toBe('erika@example.org')
        ->and($other->fresh()->email)->toBe('andere@example.org');
});

it('replaces an earlier pending request with a new one', function () {
    Mail::fake();
    $user = accUser();
    $this->actingAs($user);

    $this->post(route('account.email.store'), ['new_email' => 'eins@example.org', 'password' => 'password']);
    $this->post(route('account.email.store'), ['new_email' => 'zwei@example.org', 'password' => 'password']);

    $statuses = $user->emailChangeRequests()->orderBy('id')->get()->map(fn ($r) => $r->status);

    expect($statuses->all())->toBe([EmailChangeRequestStatus::Superseded, EmailChangeRequestStatus::Pending]);
});

it('refuses addresses that are taken, the same address, a wrong password and too many requests', function () {
    Mail::fake();
    User::factory()->create(['email' => 'vergeben@example.org']);
    $user = accUser();
    $this->actingAs($user);

    $this->post(route('account.email.store'), ['new_email' => 'vergeben@example.org', 'password' => 'password'])->assertSessionHasErrors('new_email');
    $this->post(route('account.email.store'), ['new_email' => 'erika@example.org', 'password' => 'password'])->assertSessionHasErrors('new_email');
    $this->post(route('account.email.store'), ['new_email' => 'neu@example.org', 'password' => 'falsch'])->assertSessionHasErrors('password');
    $this->post(route('account.email.store'), ['new_email' => 'kein-mail', 'password' => 'password'])->assertSessionHasErrors('new_email');
    Mail::assertNothingSent();

    foreach (['a', 'b', 'c'] as $letter) {
        $this->post(route('account.email.store'), ['new_email' => "{$letter}@example.org", 'password' => 'password'])->assertSessionHasNoErrors();
    }

    $this->post(route('account.email.store'), ['new_email' => 'd@example.org', 'password' => 'password'])->assertSessionHasErrors('new_email');
});

// ---------- Sitzungen ----------

it('lists the current session and explains the limited list outside the database driver', function () {
    $this->actingAs(accUser())
        ->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:130.0) Gecko/20100101 Firefox/130.0')
        ->get(route('account.sessions'))
        ->assertOk()
        ->assertSee('Firefox auf Windows')
        ->assertSee('Diese Sitzung')
        ->assertSee('nur die aktuelle Sitzung');
});

it('ends all other sessions with the password', function () {
    $user = accUser();
    $this->actingAs($user);

    $this->delete(route('account.sessions.destroy'), ['password' => 'falsch'])->assertSessionHasErrors('password');
    expect($user->fresh()->session_version)->toBe(1);

    $this->delete(route('account.sessions.destroy'), ['password' => 'password'])->assertRedirect(route('account.sessions'))->assertSessionHas('success');

    expect($user->fresh()->session_version)->toBe(2);
    $this->get(route('account.sessions'))->assertOk();
});

it('describes browsers and systems from the user agent', function () {
    expect(SessionsController::describe('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36 Edg/126.0'))->toBe('Edge auf Windows')
        ->and(SessionsController::describe('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Version/17.0 Mobile/15E148 Safari/604.1'))->toBe('Safari auf iOS')
        ->and(SessionsController::describe('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/126.0 Safari/537.36'))->toBe('Chrome auf Linux')
        ->and(SessionsController::describe(''))->toBe('Unbekannter Browser');
});
