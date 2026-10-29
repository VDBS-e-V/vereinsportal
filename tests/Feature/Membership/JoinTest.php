<?php

use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\Membership\Enums\MembershipStatus;
use App\Modules\Membership\Mail\ApplicationNoticeMail;
use App\Modules\Membership\Mail\RegistrationLinkMail;
use App\Modules\Membership\Mail\VerifyApplicantEmailMail;
use App\Modules\Membership\Models\Membership;
use App\Modules\Membership\Services\MembershipRegistrations;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Mail::fake();
});

function jnBoard(): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    RoleAssignment::query()->create([
        'user_id' => $user->id,
        'role_id' => Role::query()->where('key', RoleKey::BoardMember->value)->value('id'),
        'source' => RoleAssignmentSource::Manual,
        'starts_at' => now()->subMinute(),
    ]);

    return $user;
}

function jnApplication(array $overrides = []): array
{
    return $overrides + [
        'first_name' => 'Erika',
        'last_name' => 'Musterfrau',
        'birth_date' => '1985-04-12',
        'email' => 'Erika@Example.org',
        'password' => 'Sicheres-Passwort-2026',
        'password_confirmation' => 'Sicheres-Passwort-2026',
        'statute' => '1',
        'privacy' => '1',
        'website' => '',
    ];
}

function jnRoleKeys(User $user): array
{
    return $user->activeRoleAssignments()->with('role')->get()->pluck('role.key')->all();
}

// ---------- Online-Antrag ----------

it('shows the application form to guests and lists it in the start navigation', function () {
    $this->get(route('join.apply'))->assertOk()->assertSee('Mitgliedschaft beantragen')->assertSee('Ich erkenne die Satzung');

    preg_match('/<header class="vdbs-header".*?<\/header>/s', $this->get(route('join.apply'))->getContent(), $header);

    expect($header[0])->toContain('>Mitglied werden<')->toMatch('/aria-current="page"\s*>Mitglied werden</');
});

it('creates person, pending account and a requested membership from an online application', function () {
    $this->post(route('join.apply.store'), jnApplication())->assertRedirect(route('join.applied'));

    $user = User::query()->where('email', 'erika@example.org')->firstOrFail();
    $membership = Membership::query()->firstOrFail();

    expect($user->status)->toBe(UserStatus::PendingVerification)
        ->and($user->email_verified_at)->toBeNull()
        ->and($user->person->fullName())->toBe('Erika Musterfrau')
        ->and($user->person->birth_date->toDateString())->toBe('1985-04-12')
        ->and($user->person->country_code)->toBe('DE')
        ->and($membership->person_id)->toBe($user->person_id)
        ->and($membership->status)->toBe(MembershipStatus::Requested)
        ->and($membership->source)->toBe('online')
        ->and($membership->events()->first()->actor_label)->toBe('Online-Antrag')
        ->and(jnRoleKeys($user))->toBe([]);

    Mail::assertSent(VerifyApplicantEmailMail::class, fn (VerifyApplicantEmailMail $mail): bool => $mail->hasTo('erika@example.org'));
});

it('shows the same thank-you page after the application', function () {
    $this->post(route('join.apply.store'), jnApplication())->assertRedirect(route('join.applied'));

    $this->get(route('join.applied'))->assertOk()->assertSee('Wir haben Ihnen eine E-Mail gesendet')->assertSee('erika@example.org');
});

it('validates the application', function () {
    $this->post(route('join.apply.store'), [])->assertSessionHasErrors(['first_name', 'last_name', 'birth_date', 'email', 'password', 'statute', 'privacy']);
    $this->post(route('join.apply.store'), jnApplication(['password' => 'kurz', 'password_confirmation' => 'kurz']))->assertSessionHasErrors('password');
    $this->post(route('join.apply.store'), jnApplication(['password_confirmation' => 'anders']))->assertSessionHasErrors('password');
    $this->post(route('join.apply.store'), jnApplication(['birth_date' => now()->addDay()->format('Y-m-d')]))->assertSessionHasErrors('birth_date');
    $this->post(route('join.apply.store'), jnApplication(['email' => 'kaputt']))->assertSessionHasErrors('email');
    $this->post(route('join.apply.store'), jnApplication(['statute' => null]))->assertSessionHasErrors('statute');
    $this->post(route('join.apply.store'), jnApplication(['privacy' => null]))->assertSessionHasErrors('privacy');

    expect(Membership::query()->count())->toBe(0)->and(User::query()->count())->toBe(0);
});

it('creates nothing when the hidden bot field is filled but answers as usual', function () {
    $this->post(route('join.apply.store'), jnApplication(['website' => 'http://spam.example']))->assertRedirect(route('join.applied'));

    expect(Membership::query()->count())->toBe(0)->and(User::query()->count())->toBe(0);
    Mail::assertNothingSent();
});

it('limits applications per visitor address', function () {
    foreach (range(1, 10) as $i) {
        $this->post(route('join.apply.store'), jnApplication(['email' => "person{$i}@example.org", 'first_name' => "Person{$i}"]))->assertRedirect(route('join.applied'));
    }

    $this->post(route('join.apply.store'), jnApplication(['email' => 'elfte@example.org', 'first_name' => 'Elfte']))->assertStatus(429);
});

// ---------- Dubletten ----------

it('answers a duplicate email neutrally, creates nothing and tells the address what to do', function () {
    $account = User::factory()->create(['email' => 'erika@example.org']);

    $this->post(route('join.apply.store'), jnApplication())->assertRedirect(route('join.applied'));

    expect(Membership::query()->count())->toBe(0)->and(User::query()->where('email', 'erika@example.org')->count())->toBe(1);

    Mail::assertSent(ApplicationNoticeMail::class, fn (ApplicationNoticeMail $mail): bool => $mail->hasTo('erika@example.org') && $mail->hasAccount === true);
    Mail::assertNotSent(VerifyApplicantEmailMail::class);
    expect($account->fresh()->status)->toBe(UserStatus::Active);
});

it('answers an email that belongs to a paper membership neutrally', function () {
    Membership::factory()->manual()->create(['email' => 'erika@example.org']);

    $this->post(route('join.apply.store'), jnApplication())->assertRedirect(route('join.applied'));

    expect(Membership::query()->count())->toBe(1);
    Mail::assertSent(ApplicationNoticeMail::class, fn (ApplicationNoticeMail $mail): bool => $mail->hasTo('erika@example.org') && $mail->hasAccount === false);
});

it('answers a known person with another email neutrally', function () {
    Membership::factory()->manual()->create(['first_name' => 'Erika', 'last_name' => 'Musterfrau', 'birth_date' => '1985-04-12', 'email' => 'alt@example.org']);

    $this->post(route('join.apply.store'), jnApplication(['email' => 'neu@example.org']))->assertRedirect(route('join.applied'));

    expect(Membership::query()->count())->toBe(1)->and(User::query()->count())->toBe(0);
    Mail::assertSent(ApplicationNoticeMail::class, fn (ApplicationNoticeMail $mail): bool => $mail->hasTo('neu@example.org'));
});

it('does not let the response reveal whether a member exists', function () {
    Membership::factory()->manual()->create(['email' => 'bekannt@example.org']);

    $known = $this->post(route('join.apply.store'), jnApplication(['email' => 'bekannt@example.org']));
    $unknown = $this->post(route('join.apply.store'), jnApplication(['email' => 'unbekannt@example.org', 'first_name' => 'Fremd']));

    expect($known->headers->get('Location'))->toBe($unknown->headers->get('Location'));
});

// ---------- E-Mail bestätigen ----------

function jnVerifyUrl(): string
{
    $url = null;

    Mail::assertSent(VerifyApplicantEmailMail::class, function (VerifyApplicantEmailMail $mail) use (&$url): bool {
        $url = $mail->url;

        return true;
    });

    return $url;
}

it('activates the account with the signed link and lets the applicant log in without any area', function () {
    $this->post(route('join.apply.store'), jnApplication());
    $user = User::query()->where('email', 'erika@example.org')->firstOrFail();

    $this->post('/login', ['email' => 'erika@example.org', 'password' => 'Sicheres-Passwort-2026'])->assertSessionHasErrors('email');

    $this->get(jnVerifyUrl())->assertOk()->assertSee('Ihr Konto ist aktiv');

    expect($user->fresh()->status)->toBe(UserStatus::Active)->and($user->fresh()->email_verified_at)->not->toBeNull();

    $this->post('/login', ['email' => 'erika@example.org', 'password' => 'Sicheres-Passwort-2026'])->assertRedirect();
    $this->assertAuthenticated();

    preg_match('/<header class="vdbs-header".*?<\/header>/s', $this->get(route('account.profile'))->assertOk()->getContent(), $header);
    expect($header[0])->toContain('Seiten im Bereich Konto')->not->toContain('>Vorstand<')->not->toContain('>Geschäftsstelle<');
});

it('rejects a tampered or wrong verification link and a verified link stays idempotent', function () {
    $this->post(route('join.apply.store'), jnApplication());
    $user = User::query()->where('email', 'erika@example.org')->firstOrFail();
    $url = jnVerifyUrl();

    $this->get($url.'x')->assertForbidden();
    // Gültig signiert, aber mit falschem Hash für die Adresse: keine Aktivierung
    $wrong = URL::temporarySignedRoute('join.verify', now()->addHour(), ['user' => $user->id, 'hash' => str_repeat('a', 40)]);
    $this->get($wrong)->assertOk()->assertSee('ungültig');
    expect($user->fresh()->status)->toBe(UserStatus::PendingVerification);

    $this->get($url)->assertOk();
    $this->get($url)->assertOk()->assertSee('Ihr Konto ist aktiv');
    expect($user->fresh()->status)->toBe(UserStatus::Active);
});

it('does not reactivate a disabled account through an old link', function () {
    $this->post(route('join.apply.store'), jnApplication());
    $user = User::query()->where('email', 'erika@example.org')->firstOrFail();
    $url = jnVerifyUrl();

    $user->forceFill(['status' => UserStatus::Disabled])->save();

    $this->get($url)->assertOk()->assertSee('ungültig');
    expect($user->fresh()->status)->toBe(UserStatus::Disabled);
});

it('resends the verification link only to unverified accounts and answers the same', function () {
    $this->post(route('join.apply.store'), jnApplication());
    Mail::fake();

    $this->post(route('join.resend'), ['email' => 'erika@example.org'])->assertRedirect(route('join.applied'))->assertSessionHas('success');
    Mail::assertSent(VerifyApplicantEmailMail::class, 1);

    Mail::fake();
    $this->post(route('join.resend'), ['email' => 'niemand@example.org'])->assertRedirect(route('join.applied'))->assertSessionHas('success');
    Mail::assertNothingSent();

    User::query()->update(['status' => UserStatus::Active->value]);
    $this->post(route('join.resend'), ['email' => 'erika@example.org'])->assertSessionHas('success');
    Mail::assertNothingSent();

    $this->post(route('join.resend'), ['email' => 'kaputt'])->assertSessionHasErrors('email');
});

// ---------- Entscheidung durch den Vorstand ----------

it('gives the member role only after the board admitted the application', function () {
    $this->post(route('join.apply.store'), jnApplication());
    $user = User::query()->where('email', 'erika@example.org')->firstOrFail();
    $user->forceFill(['status' => UserStatus::Active, 'email_verified_at' => now()])->save();
    $membership = Membership::query()->firstOrFail();

    expect(jnRoleKeys($user))->toBe([]);

    $this->actingAs(jnBoard())->post(route('membership.admit', $membership), ['starts_on' => now()->format('Y-m-d')])->assertSessionHas('success');

    expect(jnRoleKeys($user))->toBe(['member']);
});

it('leaves the account without the member role when the application is rejected', function () {
    $this->post(route('join.apply.store'), jnApplication());
    $user = User::query()->where('email', 'erika@example.org')->firstOrFail();
    $user->forceFill(['status' => UserStatus::Active, 'email_verified_at' => now()])->save();
    $membership = Membership::query()->firstOrFail();

    $this->actingAs(jnBoard())->post(route('membership.reject', $membership), ['comment' => 'Unvollständig'])->assertSessionHas('success');

    expect($membership->fresh()->status)->toBe(MembershipStatus::Rejected)
        ->and($user->fresh()->status)->toBe(UserStatus::Active)
        ->and(jnRoleKeys($user))->toBe([]);
});

it('keeps application data out of the general log', function () {
    $this->post(route('join.apply.store'), jnApplication());

    $entry = AuditEntry::query()->where('event', 'user.created')->firstOrFail();

    expect($entry->context)->toBe(['selbstregistrierung' => true])
        ->and(AuditEntry::query()->get()->toJson())->not->toContain('1985')->not->toContain('erika@example.org');
});

// ---------- Registrierungslink ----------

function jnLinkUrl(): string
{
    $url = null;

    Mail::assertSent(RegistrationLinkMail::class, function (RegistrationLinkMail $mail) use (&$url): bool {
        $url = $mail->url;

        return true;
    });

    return $url;
}

it('sends a registration link to the email on file and stores only its hash', function () {
    $membership = Membership::factory()->manual()->create(['email' => 'mitglied@example.org']);

    $this->actingAs(jnBoard())->post(route('membership.registration-link', $membership))->assertSessionHas('success');

    $token = basename(parse_url(jnLinkUrl(), PHP_URL_PATH));

    Mail::assertSent(RegistrationLinkMail::class, fn (RegistrationLinkMail $mail): bool => $mail->hasTo('mitglied@example.org'));

    $membership->refresh();
    expect($token)->toHaveLength(48)
        ->and($membership->registration_token_hash)->toBe(hash('sha256', $token))
        ->and($membership->registration_token_hash)->not->toContain($token)
        ->and($membership->registration_expires_at->isFuture())->toBeTrue()
        ->and($membership->events()->first()->type->value)->toBe('registration_link_sent');
});

it('refuses a registration link without an email, for linked, ended and rejected memberships', function () {
    $this->actingAs(jnBoard());

    foreach ([
        Membership::factory()->manual()->create(['email' => null]),
        Membership::factory()->create(),
        Membership::factory()->manual()->ended()->create(['email' => 'a@example.org']),
        Membership::factory()->manual()->rejected()->create(['email' => 'b@example.org']),
    ] as $membership) {
        $this->post(route('membership.registration-link', $membership))->assertSessionHas('warning');
    }

    Mail::assertNothingSent();
});

it('lets only the board send registration links', function () {
    $membership = Membership::factory()->manual()->create(['email' => 'mitglied@example.org']);
    $person = Person::factory()->create();
    $staff = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);
    RoleAssignment::query()->create(['user_id' => $staff->id, 'role_id' => Role::query()->where('key', 'administration_staff')->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);

    $this->actingAs($staff)->post(route('membership.registration-link', $membership))->assertForbidden();
});

it('registers an existing member through the link: person, account, link and role at once', function () {
    $membership = Membership::factory()->manual()->create(['email' => 'mitglied@example.org', 'first_name' => 'Max', 'last_name' => 'Mustermann', 'birth_date' => '1970-02-03', 'starts_on' => '2020-01-01']);
    $this->actingAs(jnBoard())->post(route('membership.registration-link', $membership));
    $url = jnLinkUrl();
    $this->app['auth']->guard('web')->logout();
    $this->flushSession();

    $this->get($url)->assertOk()->assertSee('Max Mustermann')->assertSee('mitglied@example.org');

    $this->post($url, ['password' => 'Sicheres-Passwort-2026', 'password_confirmation' => 'Sicheres-Passwort-2026', 'phone' => '0341 123456', 'street' => 'Hauptstraße', 'city' => 'Leipzig', 'privacy' => '1'])
        ->assertRedirect(route('login'));

    $membership->refresh();
    $user = User::query()->where('email', 'mitglied@example.org')->firstOrFail();

    expect($user->status)->toBe(UserStatus::Active)
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->person->fullName())->toBe('Max Mustermann')
        ->and($user->person->birth_date->toDateString())->toBe('1970-02-03')
        ->and($user->person->city)->toBe('Leipzig')
        ->and($membership->person_id)->toBe($user->person_id)
        ->and($membership->linked_at)->not->toBeNull()
        ->and($membership->registration_token_hash)->toBeNull()
        ->and($membership->events()->first()->type->value)->toBe('registered')
        ->and($membership->events()->first()->actor_label)->toBe('Selbstregistrierung')
        ->and(jnRoleKeys($user))->toBe(['member']);

    $this->post('/login', ['email' => 'mitglied@example.org', 'password' => 'Sicheres-Passwort-2026'])->assertRedirect();
    $this->assertAuthenticated();
});

it('gives no role through the link while the membership is only requested', function () {
    $membership = Membership::factory()->manual()->requested()->create(['email' => 'antrag@example.org']);
    $this->actingAs(jnBoard())->post(route('membership.registration-link', $membership));
    $url = jnLinkUrl();

    $this->post($url, ['password' => 'Sicheres-Passwort-2026', 'password_confirmation' => 'Sicheres-Passwort-2026', 'privacy' => '1'])->assertRedirect(route('login'));

    expect(jnRoleKeys(User::query()->where('email', 'antrag@example.org')->firstOrFail()))->toBe([]);
});

it('uses a link only once', function () {
    $membership = Membership::factory()->manual()->create(['email' => 'mitglied@example.org']);
    $this->actingAs(jnBoard())->post(route('membership.registration-link', $membership));
    $url = jnLinkUrl();
    $data = ['password' => 'Sicheres-Passwort-2026', 'password_confirmation' => 'Sicheres-Passwort-2026', 'privacy' => '1'];

    $this->post($url, $data)->assertRedirect(route('login'));

    $this->get($url)->assertOk()->assertSee('ungültig oder abgelaufen');
    $this->post($url, $data)->assertOk()->assertSee('ungültig oder abgelaufen');
    expect(User::query()->where('email', 'mitglied@example.org')->count())->toBe(1);
});

it('rejects unknown, expired and replaced links', function () {
    $membership = Membership::factory()->manual()->create(['email' => 'mitglied@example.org']);
    $board = jnBoard();
    $this->actingAs($board)->post(route('membership.registration-link', $membership));
    $first = jnLinkUrl();

    $this->get(route('join.register', 'gibt-es-nicht'))->assertOk()->assertSee('ungültig oder abgelaufen');

    Mail::fake();
    $this->post(route('membership.registration-link', $membership));
    $second = jnLinkUrl();

    $this->get($first)->assertSee('ungültig oder abgelaufen');
    $this->get($second)->assertSee('Registrierung');

    $membership->refresh()->forceFill(['registration_expires_at' => now()->subMinute()])->save();
    $this->get($second)->assertSee('ungültig oder abgelaufen');

    expect(app(MembershipRegistrations::class)->find(''))->toBeNull()->and(app(MembershipRegistrations::class)->find(str_repeat('x', 101)))->toBeNull();
});

it('validates the registration and refuses an email that has meanwhile been taken', function () {
    $membership = Membership::factory()->manual()->create(['email' => 'mitglied@example.org']);
    $this->actingAs(jnBoard())->post(route('membership.registration-link', $membership));
    $url = jnLinkUrl();

    $this->post($url, [])->assertSessionHasErrors(['password', 'privacy']);
    $this->post($url, ['password' => 'kurz', 'password_confirmation' => 'kurz', 'privacy' => '1'])->assertSessionHasErrors('password');
    $this->post($url, ['password' => 'Sicheres-Passwort-2026', 'password_confirmation' => 'Sicheres-Passwort-2026', 'privacy' => '1', 'phone' => 'abc'])->assertSessionHasErrors('phone');

    User::factory()->create(['email' => 'mitglied@example.org']);

    $this->post($url, ['password' => 'Sicheres-Passwort-2026', 'password_confirmation' => 'Sicheres-Passwort-2026', 'privacy' => '1'])->assertSessionHasErrors('password');
    expect($membership->fresh()->person_id)->toBeNull();
});

it('asks for the birth date when the paper record has none', function () {
    $membership = Membership::factory()->manual()->create(['email' => 'mitglied@example.org', 'birth_date' => null]);
    $this->actingAs(jnBoard())->post(route('membership.registration-link', $membership));
    $url = jnLinkUrl();
    $data = ['password' => 'Sicheres-Passwort-2026', 'password_confirmation' => 'Sicheres-Passwort-2026', 'privacy' => '1'];

    $this->get($url)->assertSee('Geburtsdatum');
    $this->post($url, $data)->assertSessionHasErrors('birth_date');

    $this->post($url, $data + ['birth_date' => '1975-06-07'])->assertRedirect(route('login'));
    expect($membership->fresh()->birth_date->toDateString())->toBe('1975-06-07');
});
