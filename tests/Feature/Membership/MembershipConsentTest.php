<?php

use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Membership\Mail\NewApplicationMail;
use App\Modules\Membership\Mail\RegistrationLinkMail;
use App\Modules\Membership\Mail\VerifyApplicantEmailMail;
use App\Modules\Membership\Models\Membership;
use App\Modules\Membership\Models\MembershipConsent;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Mail::fake();
});

function mcUser(RoleKey $role, array $assignment = [], array $user = []): User
{
    $person = Person::factory()->create();
    $account = User::factory()->create($user + ['person_id' => $person->id, 'email' => $person->email]);

    RoleAssignment::query()->create($assignment + [
        'user_id' => $account->id,
        'role_id' => Role::query()->where('key', $role->value)->value('id'),
        'source' => RoleAssignmentSource::Manual,
        'starts_at' => now()->subMinute(),
    ]);

    return $account;
}

function mcApply(array $overrides = []): void
{
    test()->post(route('join.apply.store'), $overrides + [
        'first_name' => 'Erika', 'last_name' => 'Musterfrau', 'birth_date' => '1985-04-12', 'email' => 'erika@example.org',
        'password' => 'Sicheres-Passwort-2026', 'password_confirmation' => 'Sicheres-Passwort-2026', 'statute' => '1', 'privacy' => '1', 'website' => '',
    ]);
}

function mcVerify(): void
{
    $url = null;

    Mail::assertSent(VerifyApplicantEmailMail::class, function (VerifyApplicantEmailMail $mail) use (&$url): bool {
        $url = $mail->url;

        return true;
    });

    test()->get($url)->assertOk();
}

// ---------- Einwilligungsnachweise ----------

it('records the consent to statute and privacy notice with version and time at the online application', function () {
    config(['portal.legal.statute_version' => '2026-03', 'portal.legal.privacy_version' => '2026-02']);

    mcApply();

    $consents = Membership::query()->firstOrFail()->consents;

    expect($consents->pluck('type')->all())->toBe(['statute', 'privacy'])
        ->and($consents->pluck('version')->all())->toBe(['2026-03', '2026-02'])
        ->and($consents->first()->accepted_at->diffInSeconds(now(), true))->toBeLessThan(60);
});

it('records no consent when the application is not created', function () {
    mcApply(['statute' => null]);
    mcApply(['website' => 'spam']);

    expect(MembershipConsent::query()->count())->toBe(0);
});

it('records the privacy consent at the registration through the link', function () {
    $membership = Membership::factory()->manual()->create(['email' => 'mitglied@example.org']);
    $board = mcUser(RoleKey::BoardMember);
    $this->actingAs($board)->post(route('membership.registration-link', $membership));

    $url = null;
    Mail::assertSent(RegistrationLinkMail::class, function (RegistrationLinkMail $mail) use (&$url): bool {
        $url = $mail->url;

        return true;
    });

    $this->post($url, ['password' => 'Sicheres-Passwort-2026', 'password_confirmation' => 'Sicheres-Passwort-2026', 'privacy' => '1']);

    expect($membership->fresh()->consents->pluck('type')->all())->toBe(['privacy']);
});

it('keeps consent records immutable', function () {
    mcApply();
    $consent = MembershipConsent::query()->firstOrFail();

    expect(fn () => $consent->update(['version' => 'x']))->toThrow(LogicException::class);
});

it('shows the consents to the board and a hint for membership without any', function () {
    mcApply();
    $online = Membership::query()->firstOrFail();
    $manual = Membership::factory()->manual()->create();
    $this->actingAs(mcUser(RoleKey::BoardMember));

    $this->get(route('membership.show', $online))->assertOk()->assertSee('Einwilligungen')->assertSee('Satzung anerkannt')->assertSee('Datenschutzhinweise zur Kenntnis genommen')->assertSee('2026-01');
    $this->get(route('membership.show', $manual))->assertOk()->assertSee('Keine Einwilligungen im Portal erfasst');
});

// ---------- Hinweis an den Vorstand ----------

it('notifies the active board once when the applicant has confirmed the email', function () {
    $board = mcUser(RoleKey::BoardMember);
    $second = mcUser(RoleKey::BoardMember);

    mcApply();
    Mail::assertNotSent(NewApplicationMail::class);

    mcVerify();

    Mail::assertSent(NewApplicationMail::class, 2);
    Mail::assertSent(NewApplicationMail::class, fn (NewApplicationMail $mail): bool => $mail->hasTo($board->email) && $mail->url === route('membership.show', Membership::query()->firstOrFail()));
    Mail::assertSent(NewApplicationMail::class, fn (NewApplicationMail $mail): bool => $mail->hasTo($second->email));
    expect(Membership::query()->firstOrFail()->board_notified_at)->not->toBeNull();
});

it('does not notify twice when the link is opened again', function () {
    mcUser(RoleKey::BoardMember);
    mcApply();
    mcVerify();
    mcVerify();

    Mail::assertSent(NewApplicationMail::class, 1);
});

it('notifies only active accounts with a valid board role', function () {
    mcUser(RoleKey::BoardMember, [], ['status' => UserStatus::Disabled]);
    mcUser(RoleKey::BoardMember, ['ends_at' => now()->subDay()]);
    mcUser(RoleKey::Administration);
    mcUser(RoleKey::AdministrationStaff);
    $board = mcUser(RoleKey::BoardMember);

    mcApply();
    mcVerify();

    Mail::assertSent(NewApplicationMail::class, 1);
    Mail::assertSent(NewApplicationMail::class, fn (NewApplicationMail $mail): bool => $mail->hasTo($board->email));
});

it('sends no personal data in the notification', function () {
    mcUser(RoleKey::BoardMember);
    mcApply();
    mcVerify();

    Mail::assertSent(NewApplicationMail::class, function (NewApplicationMail $mail): bool {
        $html = $mail->render();

        return str_contains($html, 'Antrag ansehen') && ! str_contains($html, 'Erika') && ! str_contains($html, 'Musterfrau') && ! str_contains($html, 'erika@example.org');
    });
});

it('can switch the notification off and does not notify for other paths', function () {
    mcUser(RoleKey::BoardMember);
    config(['portal.membership.notify_board' => false]);

    mcApply();
    mcVerify();

    Mail::assertNotSent(NewApplicationMail::class);
});

it('does not notify for duplicates or paper applications', function () {
    Storage::fake('local');
    $board = mcUser(RoleKey::BoardMember);
    Membership::factory()->manual()->create(['email' => 'erika@example.org']);

    mcApply();

    $this->actingAs($board)->post(route('membership.store'), [
        'first_name' => 'Papier', 'last_name' => 'Antrag', 'mode' => 'request', 'kind' => 'application',
        'document' => Illuminate\Http\UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'),
    ]);

    Mail::assertNotSent(NewApplicationMail::class);
});
