<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\Onboarding\Mail\TeamOnboardingReminderMail;
use App\Modules\Onboarding\Models\TeamOnboarding;
use App\Modules\Onboarding\Services\TeamOnboardings;
use App\Modules\People\Models\Person;
use App\Modules\Seminars\Enums\ApplicationStatus;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Mail\SeminarDeadlineMail;
use App\Modules\Seminars\Mail\SeminarTeamMail;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use App\Modules\Seminars\Models\TeamProfile;
use App\Modules\Seminars\Services\SeminarAdministration;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Mail::fake();
});

function taUser(?RoleKey $role = null): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    if ($role !== null) {
        RoleAssignment::query()->create([
            'user_id' => $user->id,
            'role_id' => Role::query()->where('key', $role->value)->value('id'),
            'source' => RoleAssignmentSource::Manual,
            'starts_at' => now()->subMinute(),
        ]);
    }

    return $user;
}

function taSeminar(array $attributes = [], ?string $start = null): Seminar
{
    $seminar = Seminar::factory()->create($attributes + ['status' => SeminarStatus::Scheduled, 'team_needed' => 2, 'application_deadline' => now()->addWeek()->toDateString()]);
    $day = $start ?? now()->addMonth()->toDateString();
    app(SeminarAdministration::class)->addSession($seminar, ['date' => $day, 'start' => '09:00', 'end' => '15:00'], taUser(RoleKey::Coordination));

    return $seminar;
}

it('shows open seminars to Teamende only', function () {
    $seminar = taSeminar(['title' => 'SV-Tage Nord']);

    $this->get(route('seminar-applications.index'))->assertRedirect(route('login'));
    $this->actingAs(taUser(RoleKey::Coordination))->get(route('seminar-applications.index'))->assertForbidden();

    $this->actingAs(taUser(RoleKey::Team))->get(route('seminar-applications.index'))->assertOk()->assertSee('SV-Tage Nord')->assertSee(now()->addWeek()->format('d.m.Y'))->assertSee('Interesse bekunden');
});

it('hides seminars that are not scheduled, have no need or are past the deadline', function () {
    taSeminar(['title' => 'Nur Anfrage', 'status' => SeminarStatus::Request]);
    taSeminar(['title' => 'Ohne Bedarf', 'team_needed' => null]);
    taSeminar(['title' => 'Stichtag vorbei', 'application_deadline' => now()->subDay()->toDateString()]);

    $this->actingAs(taUser(RoleKey::Team))->get(route('seminar-applications.index'))->assertOk()
        ->assertDontSee('Nur Anfrage')->assertDontSee('Ohne Bedarf')->assertDontSee('Stichtag vorbei')->assertSee('Zurzeit keine Seminare mit offenem Stichtag');
});

it('keeps the deadline day itself open', function () {
    taSeminar(['title' => 'Stichtag heute', 'application_deadline' => now()->toDateString()]);

    $this->actingAs(taUser(RoleKey::Team))->get(route('seminar-applications.index'))->assertSee('Stichtag heute');
});

it('lets Teamende apply, blocks duplicates, allows withdrawing and applying again', function () {
    $seminar = taSeminar();
    $team = taUser(RoleKey::Team);

    $this->actingAs($team)->post(route('seminar-applications.apply', $seminar), ['note' => 'Am zweiten Tag erst ab 10 Uhr', 'wants_lead' => '1'])->assertRedirect(route('seminar-applications.index'));

    expect(SeminarApplication::query()->where('seminar_id', $seminar->id)->where('user_id', $team->id)->value('status'))->toBe(ApplicationStatus::Applied);
    expect($seminar->events()->where('type', 'team_applied')->count())->toBe(1)->and(SeminarApplication::query()->first()->wants_lead)->toBeTrue();

    $this->actingAs($team)->post(route('seminar-applications.apply', $seminar))->assertSessionHasErrors('apply');
    expect(SeminarApplication::query()->count())->toBe(1);

    $this->actingAs($team)->delete(route('seminar-applications.withdraw', $seminar))->assertSessionHasNoErrors();
    expect(SeminarApplication::query()->first()->status)->toBe(ApplicationStatus::Withdrawn);

    $this->actingAs($team)->post(route('seminar-applications.apply', $seminar))->assertSessionHasNoErrors();
    expect(SeminarApplication::query()->count())->toBe(1)->and(SeminarApplication::query()->first()->status)->toBe(ApplicationStatus::Applied);
});

it('does not accept applications for seminars that are closed', function () {
    $seminar = taSeminar(['status' => SeminarStatus::Planning]);

    $this->actingAs(taUser(RoleKey::Team))->post(route('seminar-applications.apply', $seminar))->assertSessionHasErrors('apply');
    expect(SeminarApplication::query()->count())->toBe(0);
});

it('does not accept interest after the deadline', function () {
    $seminar = taSeminar(['application_deadline' => now()->subDay()->toDateString()]);

    $this->actingAs(taUser(RoleKey::Team))->post(route('seminar-applications.apply', $seminar))->assertSessionHasErrors('apply');
    expect(SeminarApplication::query()->count())->toBe(0);
});

it('lets the coordination set need, deadline and wanted competences and requires a deadline', function () {
    $seminar = taSeminar(['team_needed' => null, 'application_deadline' => null]);
    $coordination = taUser(RoleKey::Coordination);

    $this->actingAs($coordination)->put(route('seminars.team.need', $seminar), ['team_needed' => 2])->assertSessionHasErrors('application_deadline');
    $this->actingAs($coordination)->put(route('seminars.team.need', $seminar), ['team_needed' => 2, 'application_deadline' => now()->addDays(10)->toDateString(), 'team_competences' => ['moderation', 'unbekannt']])->assertSessionHasErrors('team_competences.1');

    $this->actingAs($coordination)->put(route('seminars.team.need', $seminar), ['team_needed' => 2, 'application_deadline' => now()->addDays(10)->toDateString(), 'team_competences' => ['moderation', 'elections']])->assertSessionHasNoErrors();

    $seminar->refresh();
    expect($seminar->team_needed)->toBe(2)->and($seminar->application_deadline->toDateString())->toBe(now()->addDays(10)->toDateString())->and($seminar->team_competences)->toBe(['elections', 'moderation']);
    expect($seminar->events()->where('type', 'team_need_changed')->count())->toBe(1);

    $this->actingAs(taUser(RoleKey::Team))->get(route('seminar-applications.index'))->assertSee('Moderation und Gesprächsführung');

    $this->actingAs($coordination)->put(route('seminars.team.need', $seminar), ['team_needed' => 0])->assertSessionHasNoErrors();
    expect($seminar->fresh()->team_needed)->toBeNull();
});

it('lets the coordination decide only after the deadline, with notifications and a log without names', function () {
    $seminar = taSeminar(['team_needed' => 1]);
    $coordination = taUser(RoleKey::Coordination);
    $a = taUser(RoleKey::Team);
    $b = taUser(RoleKey::Team);

    $this->actingAs($a)->post(route('seminar-applications.apply', $seminar));
    $this->actingAs($b)->post(route('seminar-applications.apply', $seminar));

    $first = SeminarApplication::query()->where('user_id', $a->id)->first();
    $second = SeminarApplication::query()->where('user_id', $b->id)->first();

    $this->actingAs($coordination)->post(route('seminars.team.decide', [$seminar, $first, 'bestaetigen']))->assertSessionHasErrors('team');
    expect($first->fresh()->status)->toBe(ApplicationStatus::Applied);

    $seminar->forceFill(['application_deadline' => now()->subDay()->toDateString()])->save();

    $this->actingAs(taUser(RoleKey::Team))->post(route('seminars.team.decide', [$seminar, $first, 'bestaetigen']))->assertForbidden();

    $this->actingAs($coordination)->post(route('seminars.team.decide', [$seminar, $first, 'bestaetigen']))->assertSessionHasNoErrors();
    Mail::assertSent(SeminarTeamMail::class, fn (SeminarTeamMail $mail): bool => $mail->hasTo($a->email) && $mail->kind === 'confirmed');

    // Bedarf gedeckt: weitere Aufnahme nicht möglich, Absage schon
    $this->actingAs($coordination)->post(route('seminars.team.decide', [$seminar, $second, 'bestaetigen']))->assertSessionHasErrors('team');
    $this->actingAs($coordination)->post(route('seminars.team.decide', [$seminar, $second, 'ablehnen']))->assertSessionHasNoErrors();
    Mail::assertSent(SeminarTeamMail::class, fn (SeminarTeamMail $mail): bool => $mail->hasTo($b->email) && $mail->kind === 'declined');

    expect($first->fresh()->status)->toBe(ApplicationStatus::Confirmed)->and($second->fresh()->status)->toBe(ApplicationStatus::Declined);

    $this->actingAs($coordination)->get(route('seminars.show', $seminar))->assertOk()->assertSee('Interessenbekundungen')->assertSee($a->person->fullName());

    foreach (AuditEntry::query()->where('event', 'seminar.team_decided')->get() as $entry) {
        expect(json_encode($entry->context, JSON_UNESCAPED_UNICODE))->not->toContain($a->person->fullName())->not->toContain($a->email);
    }
    expect(AuditEntry::query()->where('event', 'seminar.team_decided')->count())->toBe(2);
});

it('shows age, gender, competences, experience and the team composition to the coordination', function () {
    $seminar = taSeminar(['team_needed' => 2, 'application_deadline' => now()->subDay()->toDateString(), 'team_competences' => ['moderation', 'elections']]);
    $coordination = taUser(RoleKey::Coordination);

    $a = taUser(RoleKey::Team);
    $a->person->forceFill(['birth_date' => now()->subYears(30)->subDays(5)->toDateString()])->save();
    $b = taUser(RoleKey::Team);
    $b->person->forceFill(['birth_date' => now()->subYears(22)->subDays(5)->toDateString()])->save();

    TeamProfile::query()->create(['user_id' => $a->id, 'competences' => ['moderation'], 'experience' => 'Drei Jahre SV-Beratung', 'lead_experience' => true, 'gender' => 'female']);
    TeamProfile::query()->create(['user_id' => $b->id, 'competences' => ['elections'], 'gender' => 'male']);

    $done = taSeminar(['title' => 'Früher', 'status' => SeminarStatus::Done]);
    SeminarApplication::query()->create(['seminar_id' => $done->id, 'user_id' => $a->id, 'status' => ApplicationStatus::Confirmed]);

    SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'user_id' => $a->id, 'status' => ApplicationStatus::Confirmed, 'wants_lead' => true]);
    SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'user_id' => $b->id, 'status' => ApplicationStatus::Confirmed]);

    $this->actingAs($coordination)->get(route('seminars.show', $seminar))->assertOk()
        ->assertSee('30 Jahre')->assertSee('22 Jahre')->assertSee('weiblich')->assertSee('männlich')
        ->assertSee('Drei Jahre SV-Beratung')->assertSee('1 Seminar(e) im Portal')->assertSee('möchte leiten')
        ->assertSee('Zusammensetzung des Teams')->assertSee('Durchschnittsalter')->assertSee('26 Jahre')
        ->assertSee('Moderation und Gesprächsführung: abgedeckt')->assertSee('Wahlen und Wahlorganisation: abgedeckt');
});

it('shows missing competences in the team composition', function () {
    $seminar = taSeminar(['team_needed' => 1, 'application_deadline' => now()->subDay()->toDateString(), 'team_competences' => ['media']]);
    $a = taUser(RoleKey::Team);
    TeamProfile::query()->create(['user_id' => $a->id, 'competences' => ['moderation']]);
    SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'user_id' => $a->id, 'status' => ApplicationStatus::Confirmed]);

    $this->actingAs(taUser(RoleKey::Coordination))->get(route('seminars.show', $seminar))->assertSee('Medien und digitale Beteiligung: fehlt');
});

it('keeps internal notes on interest declarations with the coordination', function () {
    $seminar = taSeminar();
    $team = taUser(RoleKey::Team);
    $application = SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'user_id' => $team->id, 'status' => ApplicationStatus::Applied]);

    $this->actingAs($team)->put(route('seminars.team.note', [$seminar, $application]), ['internal_note' => 'x'])->assertForbidden();
    $this->actingAs(taUser(RoleKey::Coordination))->put(route('seminars.team.note', [$seminar, $application]), ['internal_note' => 'Gute Moderation beim letzten Mal'])->assertSessionHasNoErrors();

    expect($application->fresh()->internal_note)->toBe('Gute Moderation beim letzten Mal');
    $this->actingAs($team)->get(route('seminar-applications.index'))->assertDontSee('Gute Moderation beim letzten Mal');
});

it('lets Teamende keep a profile that only they and the coordination can use', function () {
    $this->get(route('team-profile.edit'))->assertRedirect(route('login'));
    $this->actingAs(taUser(RoleKey::Coordination))->get(route('team-profile.edit'))->assertForbidden();

    $team = taUser(RoleKey::Team);
    $this->actingAs($team)->get(route('team-profile.edit'))->assertOk()->assertSee('freiwillig');

    $this->actingAs($team)->put(route('team-profile.update'), ['competences' => ['moderation', 'media'], 'experience' => 'SV-Wahl begleitet', 'lead_experience' => '1', 'gender' => 'diverse'])->assertSessionHasNoErrors();

    $profile = TeamProfile::query()->where('user_id', $team->id)->firstOrFail();
    expect($profile->competences)->toBe(['moderation', 'media'])->and($profile->lead_experience)->toBeTrue()->and($profile->gender->value)->toBe('diverse');

    $this->actingAs($team)->get(route('team-profile.edit'))->assertSee('SV-Wahl begleitet');

    $this->actingAs($team)->put(route('team-profile.update'), ['competences' => ['unbekannt'], 'gender' => 'x'])->assertSessionHasErrors(['competences.0', 'gender']);

    $this->actingAs($team)->put(route('team-profile.update'), ['competences' => [], 'gender' => ''])->assertSessionHasNoErrors();
    expect($profile->fresh()->gender)->toBeNull()->and($profile->fresh()->competences)->toBe([]);
});

it('reminds Teamende to fill in their profile', function () {
    $this->actingAs(taUser(RoleKey::Team))->get(route('seminar-applications.index'))->assertSee('Ergänzen Sie dafür bitte Ihr');
});

it('tells the coordination once when a deadline has passed and again after a new deadline', function () {
    $due = taSeminar(['title' => 'Stichtag vorbei', 'application_deadline' => now()->subDay()->toDateString()]);
    taSeminar(['title' => 'Stichtag offen']);
    $coordination = taUser(RoleKey::Coordination);
    $team = taUser(RoleKey::Team);
    SeminarApplication::query()->create(['seminar_id' => $due->id, 'user_id' => $team->id, 'status' => ApplicationStatus::Applied]);

    $this->artisan('seminars:team-deadlines')->expectsOutputToContain('Gemeldete Seminare: 1')->assertSuccessful();
    Mail::assertSent(SeminarDeadlineMail::class, fn (SeminarDeadlineMail $mail): bool => $mail->hasTo($coordination->email) && $mail->seminar->is($due) && $mail->interested === 1);
    Mail::assertNotSent(SeminarDeadlineMail::class, fn (SeminarDeadlineMail $mail): bool => $mail->hasTo($team->email));

    $this->artisan('seminars:team-deadlines')->expectsOutputToContain('Gemeldete Seminare: 0')->assertSuccessful();

    app(\App\Modules\Seminars\Services\TeamApplications::class)->setNeed($due->fresh(), 2, now()->addDays(3), [], $coordination);
    expect($due->fresh()->deadline_notified_at)->toBeNull();
});

it('warns about overlaps with confirmed seminars of the applicant', function () {
    $day = now()->addMonth()->toDateString();
    $confirmed = taSeminar(['title' => 'Schon zugesagt'], $day);
    $other = taSeminar(['title' => 'Gleicher Tag'], $day);
    $team = taUser(RoleKey::Team);
    SeminarApplication::query()->create(['seminar_id' => $confirmed->id, 'user_id' => $team->id, 'status' => ApplicationStatus::Confirmed]);

    $this->actingAs($team)->get(route('seminar-applications.index'))->assertOk()->assertSee('überschneidet sich mit Ihrem bestätigten Seminar „Schon zugesagt“', false);

    SeminarApplication::query()->create(['seminar_id' => $other->id, 'user_id' => $team->id, 'status' => ApplicationStatus::Applied]);
    $this->actingAs(taUser(RoleKey::Coordination))->get(route('seminars.show', $other))->assertSee('Überschneidung: Schon zugesagt');
});

it('offers a calendar file with the confirmed seminars only', function () {
    $team = taUser(RoleKey::Team);
    $confirmed = taSeminar(['title' => 'Bestätigtes Seminar']);
    $applied = taSeminar(['title' => 'Nur beworben']);
    SeminarApplication::query()->create(['seminar_id' => $confirmed->id, 'user_id' => $team->id, 'status' => ApplicationStatus::Confirmed]);
    SeminarApplication::query()->create(['seminar_id' => $applied->id, 'user_id' => $team->id, 'status' => ApplicationStatus::Applied]);

    $response = $this->actingAs($team)->get(route('seminar-applications.calendar'))->assertOk();
    $body = $response->getContent();

    expect($response->headers->get('Content-Type'))->toContain('text/calendar')
        ->and($body)->toContain('BEGIN:VCALENDAR')->toContain('BEGIN:VEVENT')->toContain('SUMMARY:Bestätigtes Seminar')->not->toContain('Nur beworben')
        ->and(substr_count($body, 'BEGIN:VEVENT'))->toBe(1);
});

it('notifies applicants and confirmed Teamende when a seminar is cancelled', function () {
    $seminar = taSeminar();
    $a = taUser(RoleKey::Team);
    $b = taUser(RoleKey::Team);
    SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'user_id' => $a->id, 'status' => ApplicationStatus::Confirmed]);
    SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'user_id' => $b->id, 'status' => ApplicationStatus::Applied]);

    app(SeminarAdministration::class)->changeStatus($seminar, SeminarStatus::Cancelled, taUser(RoleKey::Coordination));

    Mail::assertSent(SeminarTeamMail::class, fn (SeminarTeamMail $mail): bool => $mail->kind === 'cancelled' && $mail->hasTo($a->email));
    Mail::assertSent(SeminarTeamMail::class, fn (SeminarTeamMail $mail): bool => $mail->kind === 'cancelled' && $mail->hasTo($b->email));
});

it('shows the Teamende seminars page in the navigation', function () {
    $this->actingAs(taUser(RoleKey::Team))->get(route('seminar-applications.index'))->assertOk()->assertSee('Seminare');
});

it('reminds Teamende with an open onboarding after the period, at most as often as configured', function () {
    $team = taUser(RoleKey::Team);
    $onboarding = app(TeamOnboardings::class)->ensure($team);
    Mail::fake();

    $this->artisan('onboarding:remind-team')->expectsOutputToContain('Erinnerungen: 0')->assertSuccessful();
    Mail::assertNothingSent();

    $onboarding->forceFill(['started_at' => now()->subDays(15)])->save();

    $this->artisan('onboarding:remind-team', ['--dry-run' => true])->expectsOutputToContain('Erinnerungen: 1')->assertSuccessful();
    Mail::assertNothingSent();

    $this->artisan('onboarding:remind-team')->expectsOutputToContain('Erinnerungen: 1')->assertSuccessful();
    Mail::assertSent(TeamOnboardingReminderMail::class, fn (TeamOnboardingReminderMail $mail): bool => $mail->hasTo($team->email) && $mail->open === 4 && $mail->total === 4);

    $this->artisan('onboarding:remind-team')->expectsOutputToContain('Erinnerungen: 0')->assertSuccessful();

    $onboarding->refresh()->forceFill(['last_reminded_at' => now()->subDays(15), 'reminders_sent' => 3])->save();
    $this->artisan('onboarding:remind-team')->expectsOutputToContain('Erinnerungen: 0')->assertSuccessful();

    $onboarding->forceFill(['reminders_sent' => 1, 'completed_at' => now()])->save();
    $this->artisan('onboarding:remind-team')->expectsOutputToContain('Erinnerungen: 0')->assertSuccessful();

    expect(TeamOnboarding::query()->count())->toBe(1);
});
