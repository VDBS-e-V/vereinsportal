<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Contacts\Models\TeamContact;
use App\Modules\People\Models\Person;
use App\Modules\Seminars\Enums\ApplicationStatus;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Mail\ApplicationConfirmationMail;
use App\Modules\Seminars\Mail\SeminarTeamInfoMail;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use App\Modules\Seminars\Models\SeminarTeamInvite;
use App\Modules\Seminars\Services\SeminarAdministration;
use App\Modules\Seminars\Services\TeamApplications;
use App\Modules\Seminars\Services\TeamInfo;
use App\Modules\Seminars\Services\TeamInvites;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Mail::fake();
});

function tiUser(RoleKey $role = RoleKey::Coordination): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);
    RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);

    return $user;
}

function tiSeminar(array $attributes = []): Seminar
{
    $seminar = Seminar::factory()->create($attributes + ['status' => SeminarStatus::Scheduled, 'team_needed' => 2, 'application_deadline' => now()->addWeek()->toDateString()]);
    app(SeminarAdministration::class)->addSession($seminar, ['date' => now()->addMonth()->toDateString(), 'start' => '09:00', 'end' => '15:00'], tiUser());

    return $seminar;
}

function tiForm(array $extra = []): array
{
    return array_merge(['first_name' => 'Lea', 'last_name' => 'Link', 'email' => 'Lea@Example.org', 'consent_data' => '1'], $extra);
}

it('creates a link that is shown once, stores only the hash and shuts down the old one', function () {
    $seminar = tiSeminar();
    $coordination = tiUser();

    $response = $this->actingAs($coordination)->post(route('seminars.team.invite.create', $seminar))->assertRedirect(route('seminars.show', $seminar))->assertSessionHas('invite_link');
    $link = session('invite_link');
    $token = basename(parse_url($link, PHP_URL_PATH));

    $invite = SeminarTeamInvite::query()->first();
    expect($invite->token_hash)->toBe(hash('sha256', $token))->and(json_encode($invite->getAttributes()))->not->toContain($token)->and($invite->expires_at->isAfter($seminar->application_deadline))->toBeTrue();

    $this->post(route('seminars.team.invite.create', $seminar));
    expect(SeminarTeamInvite::query()->valid()->count())->toBe(1)->and(app(TeamInvites::class)->seminarFor($token))->toBeNull();
    $this->get(route('seminars.show', $seminar))->assertOk()->assertSee('Bewerbungslink');
});

it('keeps the link management for people who manage seminars', function () {
    $seminar = tiSeminar();

    $this->post(route('seminars.team.invite.create', $seminar))->assertRedirect(route('login'));
    $this->actingAs(tiUser(RoleKey::Team))->post(route('seminars.team.invite.create', $seminar))->assertForbidden();
    expect(SeminarTeamInvite::query()->count())->toBe(0);
});

it('creates no link for seminars that are not open', function () {
    $seminar = tiSeminar(['application_deadline' => now()->subDay()->toDateString()]);

    $this->actingAs(tiUser())->post(route('seminars.team.invite.create', $seminar))->assertSessionHasErrors('team');
    expect(SeminarTeamInvite::query()->count())->toBe(0);
});

it('shows the seminar without school name and fee on the public form and a neutral page for bad links', function () {
    $seminar = tiSeminar(['title' => 'Offenes Seminar']);
    $link = app(TeamInvites::class)->create($seminar, tiUser())['link'];

    $this->get($link)->assertOk()->assertSee('Offenes Seminar')->assertDontSee($seminar->partner->name)->assertSee('Bewerbung absenden');
    $this->get(route('team-invite.show', ['token' => str_repeat('x', 40)]))->assertOk()->assertSee('Dieser Link gilt nicht mehr');
});

it('takes the application only after the confirmation of the address', function () {
    $seminar = tiSeminar();
    $token = basename(parse_url(app(TeamInvites::class)->create($seminar, tiUser())['link'], PHP_URL_PATH));

    $this->post(route('team-invite.store', ['token' => $token]), tiForm(['competences' => ['moderation'], 'wants_lead' => '1']))->assertRedirect(route('team-invite.sent'));

    $application = SeminarApplication::query()->first();
    $contact = TeamContact::query()->first();
    expect($application->confirmed_at)->toBeNull()->and($application->isConfirmedApplicant())->toBeFalse()->and($contact->origin)->toBe('link')->and($contact->email)->toBe('lea@example.org');
    Mail::assertSent(ApplicationConfirmationMail::class, fn (ApplicationConfirmationMail $mail): bool => $mail->hasTo('lea@example.org') && str_contains($mail->confirmUrl, 'signature='));

    // unbestätigt: nicht in der Auswertung, nicht entscheidbar
    $review = app(TeamApplications::class)->review($seminar->fresh('applications'));
    expect($review['rows'])->toBe([]);
    expect(fn () => app(TeamApplications::class)->decide($application, ApplicationStatus::Confirmed, tiUser()))->toThrow(DomainException::class);

    $url = URL::temporarySignedRoute('team-invite.confirm', now()->addDay(), ['application' => $application->id]);
    $this->get($url)->assertOk()->assertSee('Ihre Bewerbung zählt');

    expect($application->fresh()->confirmed_at)->not->toBeNull()
        ->and(app(TeamApplications::class)->review($seminar->fresh('applications'))['rows'])->toHaveCount(1);
});

it('rejects a tampered or expired confirmation link', function () {
    $seminar = tiSeminar();
    $contact = TeamContact::query()->create(['email' => 'x@example.org', 'origin' => 'link']);
    $application = SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'contact_id' => $contact->id, 'status' => 'applied', 'guest_name' => 'X Y']);

    $this->get(route('team-invite.confirm', ['application' => $application->id]))->assertForbidden();
    $this->get(URL::temporarySignedRoute('team-invite.confirm', now()->subMinute(), ['application' => $application->id]))->assertForbidden();
    expect($application->fresh()->confirmed_at)->toBeNull();
});

it('gives the same answer for a known address and sends the confirmation again for an open one', function () {
    $seminar = tiSeminar();
    $token = basename(parse_url(app(TeamInvites::class)->create($seminar, tiUser())['link'], PHP_URL_PATH));

    $this->post(route('team-invite.store', ['token' => $token]), tiForm())->assertRedirect(route('team-invite.sent'));
    $this->post(route('team-invite.store', ['token' => $token]), tiForm())->assertRedirect(route('team-invite.sent'));
    Mail::assertSent(ApplicationConfirmationMail::class, 2);
    expect(SeminarApplication::query()->count())->toBe(1);

    SeminarApplication::query()->update(['confirmed_at' => now()]);
    $this->post(route('team-invite.store', ['token' => $token]), tiForm())->assertRedirect(route('team-invite.sent'));
    Mail::assertSent(ApplicationConfirmationMail::class, 2);
    expect(SeminarApplication::query()->count())->toBe(1);
});

it('validates the form and needs the consent', function () {
    $seminar = tiSeminar();
    $token = basename(parse_url(app(TeamInvites::class)->create($seminar, tiUser())['link'], PHP_URL_PATH));

    $this->post(route('team-invite.store', ['token' => $token]), [])->assertSessionHasErrors(['email', 'first_name', 'last_name', 'consent_data']);
    $this->post(route('team-invite.store', ['token' => $token]), tiForm(['email' => 'kaputt']))->assertSessionHasErrors('email');
    expect(SeminarApplication::query()->count())->toBe(0);
});

it('shows the closed page after the deadline or when the link is blocked', function () {
    $seminar = tiSeminar();
    $service = app(TeamInvites::class);
    $token = basename(parse_url($service->create($seminar, tiUser())['link'], PHP_URL_PATH));

    $service->revoke($seminar, tiUser());
    $this->post(route('team-invite.store', ['token' => $token]), tiForm())->assertOk()->assertSee('Dieser Link gilt nicht mehr');
    expect(SeminarApplication::query()->count())->toBe(0);

    $token = basename(parse_url($service->create($seminar, tiUser())['link'], PHP_URL_PATH));
    $this->travel(9)->days();
    $this->get(route('team-invite.show', ['token' => $token]))->assertSee('Dieser Link gilt nicht mehr');
});

it('keeps contacts from the link out of the bulk sending of personal access', function () {
    TeamContact::query()->create(['email' => 'import@example.org']);
    TeamContact::query()->create(['email' => 'link@example.org', 'origin' => 'link']);

    $this->actingAs(tiUser(RoleKey::AdministrationStaff))->post(route('contacts.send-all'))->assertSessionHas('success');

    Mail::assertSent(\App\Modules\Contacts\Mail\ContactAccessMail::class, 1);
    Mail::assertSent(\App\Modules\Contacts\Mail\ContactAccessMail::class, fn ($mail): bool => $mail->hasTo('import@example.org'));
});

it('informs the whole team with an overview and shows contact data only with consent', function () {
    $seminar = tiSeminar(['application_deadline' => now()->subDay()->toDateString(), 'team_needed' => 3]);
    $account = tiUser(RoleKey::Team);
    $withConsent = TeamContact::query()->create(['email' => 'mit@example.org']);
    $without = TeamContact::query()->create(['email' => 'ohne@example.org']);
    SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'user_id' => $account->id, 'status' => 'confirmed']);
    SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'contact_id' => $withConsent->id, 'status' => 'confirmed', 'confirmed_at' => now(), 'guest_name' => 'Mia Mit', 'guest_phone' => '030 5', 'guest_consent_contact' => true]);
    SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'contact_id' => $without->id, 'status' => 'confirmed', 'confirmed_at' => now(), 'guest_name' => 'Otto Ohne', 'guest_phone' => '030 6', 'guest_consent_contact' => false]);
    $coordination = tiUser();

    $this->actingAs($coordination)->get(route('seminars.team.info', $seminar))->assertOk()->assertSee('Mia Mit')->assertSee('Otto Ohne')->assertSee('data-vdbs-select-all="team-info-form"', false);

    $ids = SeminarApplication::query()->pluck('id')->all();
    $this->post(route('seminars.team.info.send', $seminar), ['application_ids' => $ids, 'extra_text' => 'Treffpunkt: Haupteingang'])->assertRedirect(route('seminars.show', $seminar))->assertSessionHas('success');

    Mail::assertSent(SeminarTeamInfoMail::class, 3);
    Mail::assertSent(SeminarTeamInfoMail::class, function (SeminarTeamInfoMail $mail): bool {
        if (! $mail->hasTo('ohne@example.org')) {
            return false;
        }
        $mail->assertSeeInHtml('Treffpunkt: Haupteingang');
        $mail->assertSeeInHtml('Mia Mit');
        $mail->assertSeeInHtml('mit@example.org');
        $mail->assertDontSeeInHtml('ohne@example.org –');
        $mail->assertDontSeeInHtml('030 6');

        return true;
    });
    expect($seminar->fresh()->team_informed_at)->not->toBeNull()->and($seminar->events()->where('type', 'team_informed')->count())->toBe(1);
});

it('sends the team information only to the ticked people and skips blocked addresses', function () {
    $seminar = tiSeminar(['application_deadline' => now()->subDay()->toDateString()]);
    $a = TeamContact::query()->create(['email' => 'a@example.org']);
    $b = TeamContact::query()->create(['email' => 'b@example.org']);
    app(\App\Modules\Mailings\Services\MailSuppressions::class)->suppress('b@example.org', 'manual', null, null);
    $first = SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'contact_id' => $a->id, 'status' => 'confirmed', 'confirmed_at' => now(), 'guest_name' => 'A A']);
    $second = SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'contact_id' => $b->id, 'status' => 'confirmed', 'confirmed_at' => now(), 'guest_name' => 'B B']);

    $this->actingAs(tiUser())->post(route('seminars.team.info.send', $seminar), [])->assertSessionHasErrors('application_ids');
    $this->post(route('seminars.team.info.send', $seminar), ['application_ids' => [$second->id]])->assertSessionHasErrors('team');
    Mail::assertNothingSent();

    $this->post(route('seminars.team.info.send', $seminar), ['application_ids' => [$first->id, $second->id]])->assertSessionHas('success');
    Mail::assertSent(SeminarTeamInfoMail::class, 1);
});

it('detects a changed team after the information', function () {
    $seminar = tiSeminar(['application_deadline' => now()->subDay()->toDateString()]);
    $a = TeamContact::query()->create(['email' => 'a@example.org']);
    $b = TeamContact::query()->create(['email' => 'b@example.org']);
    $first = SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'contact_id' => $a->id, 'status' => 'confirmed', 'confirmed_at' => now(), 'guest_name' => 'A A']);
    $info = app(TeamInfo::class);

    expect($info->changedSinceLastInfo($seminar->fresh()))->toBeFalse();
    $info->send($seminar->fresh(), [$first->id], null);
    expect($info->changedSinceLastInfo($seminar->fresh()))->toBeFalse();

    SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'contact_id' => $b->id, 'status' => 'confirmed', 'confirmed_at' => now(), 'guest_name' => 'B B']);
    expect($info->changedSinceLastInfo($seminar->fresh()))->toBeTrue();
    $this->actingAs(tiUser())->get(route('seminars.team.info', $seminar))->assertSee('Das Team hat sich seit der letzten Information geändert');
});

it('keeps the team information for people who manage seminars and needs a team', function () {
    $seminar = tiSeminar();

    $this->actingAs(tiUser(RoleKey::Team))->get(route('seminars.team.info', $seminar))->assertForbidden();
    $this->actingAs(tiUser())->get(route('seminars.team.info', $seminar))->assertRedirect(route('seminars.show', $seminar));
});

it('prunes unconfirmed, withdrawn and finished applications and keeps the rest', function () {
    $ended = tiSeminar();
    $ended->sessions()->update(['starts_at' => now()->subMonths(8), 'ends_at' => now()->subMonths(8)->addHours(6)]);
    $running = tiSeminar();
    $c = fn (string $email, string $origin = 'import') => TeamContact::query()->create(['email' => $email, 'origin' => $origin]);

    $old = SeminarApplication::query()->create(['seminar_id' => $running->id, 'contact_id' => $c('alt@example.org', 'link')->id, 'status' => 'applied', 'guest_name' => 'Alt Unbestätigt']);
    SeminarApplication::query()->where('id', $old->id)->update(['created_at' => now()->subDays(8)]);
    $fresh = SeminarApplication::query()->create(['seminar_id' => $running->id, 'contact_id' => $c('neu@example.org', 'link')->id, 'status' => 'applied', 'guest_name' => 'Neu Unbestätigt']);
    $withdrawn = SeminarApplication::query()->create(['seminar_id' => $running->id, 'contact_id' => $c('zurueck@example.org')->id, 'status' => 'withdrawn', 'confirmed_at' => now(), 'guest_name' => 'Zora Zurück']);
    SeminarApplication::query()->where('id', $withdrawn->id)->update(['updated_at' => now()->subDays(31)]);
    $done = SeminarApplication::query()->create(['seminar_id' => $ended->id, 'contact_id' => $c('fertig@example.org')->id, 'status' => 'confirmed', 'confirmed_at' => now(), 'guest_name' => 'Fred Fertig', 'guest_phone' => '030', 'guest_experience' => 'viel', 'note' => 'Hallo']);
    $active = SeminarApplication::query()->create(['seminar_id' => $running->id, 'contact_id' => $c('aktiv@example.org')->id, 'status' => 'applied', 'confirmed_at' => now(), 'guest_name' => 'Anna Aktiv']);

    $this->artisan('seminars:prune-applicants', ['--dry-run' => true])->assertSuccessful();
    expect(SeminarApplication::query()->count())->toBe(5)->and($done->fresh()->guest_name)->toBe('Fred Fertig');

    $this->artisan('seminars:prune-applicants')->assertSuccessful();

    expect(SeminarApplication::query()->whereKey($old->id)->exists())->toBeFalse()
        ->and(SeminarApplication::query()->whereKey($fresh->id)->exists())->toBeTrue()
        ->and($withdrawn->fresh())->contact_id->toBeNull()->guest_name->toBeNull()
        ->and($done->fresh())->contact_id->toBeNull()->guest_name->toBeNull()->guest_phone->toBeNull()->guest_experience->toBeNull()->note->toBeNull()
        ->and($done->fresh()->applicantName())->toBe('Anonymisiert')
        ->and($active->fresh()->guest_name)->toBe('Anna Aktiv')
        ->and(TeamContact::query()->where('email', 'alt@example.org')->exists())->toBeFalse()
        ->and(TeamContact::query()->where('email', 'aktiv@example.org')->exists())->toBeTrue();
});

it('schedules the pruning daily and offers it in the system tasks', function () {
    $schedule = file_get_contents(base_path('routes/console.php'));

    expect($schedule)->toContain("Schedule::command('seminars:prune-applicants')->daily()")
        ->and(app(\App\Modules\System\Services\SystemTasks::class)->find('bewerbungen-aufraeumen')['command'])->toBe('seminars:prune-applicants');
});

it('keeps anonymized applications out of the review', function () {
    $seminar = tiSeminar(['application_deadline' => now()->subDay()->toDateString()]);
    SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'status' => 'confirmed']);

    expect(app(TeamApplications::class)->review($seminar->fresh('applications'))['rows'])->toBe([]);
});
