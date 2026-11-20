<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Partners\Models\Partner;
use App\Modules\People\Models\Person;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Mail\SeminarNoticeMail;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use App\Modules\Seminars\Services\SeminarWorkflow;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Mail::fake();
});

function snUser(?RoleKey $role = null): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

function snOptOut(User $user, string $key): void
{
    DB::table('notification_preferences')->insert(['user_id' => $user->id, 'key' => $key, 'enabled' => false, 'created_at' => now(), 'updated_at' => now()]);
}

function snSent(string $to, string $subjectStart): bool
{
    return Mail::sent(SeminarNoticeMail::class, fn (SeminarNoticeMail $mail): bool => $mail->hasTo($to) && str_starts_with($mail->subjectLine, $subjectStart))->isNotEmpty();
}

it('confirms a request to the school and tells the office, unless the office switched it off', function () {
    $wants = snUser(RoleKey::AdministrationStaff);
    $declines = snUser(RoleKey::AdministrationStaff);
    snOptOut($declines, 'seminar_requests');
    $partner = Partner::factory()->create();

    app(SeminarWorkflow::class)->request($partner, ['title' => 'Medientag', 'contact_name' => 'Frau Lehrkraft', 'contact_email' => 'lehrkraft@schule.example'], snUser());

    expect(snSent('lehrkraft@schule.example', 'Ihre Seminaranfrage'))->toBeTrue()->and(snSent($wants->email, 'Neue Seminaranfrage'))->toBeTrue()->and(snSent($declines->email, 'Neue Seminaranfrage'))->toBeFalse();
});

it('tells the school the reason when the request is rejected', function () {
    $seminar = Seminar::factory()->create(['contact_email' => 'lehrkraft@schule.example']);

    app(SeminarWorkflow::class)->transition($seminar, SeminarStatus::Rejected, snUser(RoleKey::AdministrationStaff), 'Zeitraum nicht möglich');

    Mail::assertSent(SeminarNoticeMail::class, fn (SeminarNoticeMail $mail): bool => $mail->hasTo('lehrkraft@schule.example') && in_array('Begründung: Zeitraum nicht möglich', $mail->paragraphs, true));
});

it('tells the coordination about a released seminar and the team about a tender', function () {
    $coordination = snUser(RoleKey::Coordination);
    $quiet = snUser(RoleKey::Coordination);
    snOptOut($quiet, 'seminar_released');
    $seminar = Seminar::factory()->status(SeminarStatus::QuoteSent)->create();
    $seminar->quotes()->create(['version' => 1, 'status' => 'sent', 'total_cents' => 1000, 'sent_at' => now()]);

    app(SeminarWorkflow::class)->release($seminar, snUser(RoleKey::AdministrationStaff), 'commitment', 'Zusage per E-Mail');

    expect(snSent($coordination->email, 'Seminar freigegeben'))->toBeTrue()->and(snSent($quiet->email, 'Seminar freigegeben'))->toBeFalse();

    $team = snUser(RoleKey::Team);
    $applied = snUser(RoleKey::Team);
    $muted = snUser(RoleKey::Team);
    snOptOut($muted, 'seminar_tender');
    $seminar->refresh();
    app(SeminarWorkflow::class)->plan($seminar, [['starts_at' => now()->addMonths(2), 'ends_at' => null, 'location' => 'Aula']], 2, $coordination);
    SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'user_id' => $applied->id]);
    app(SeminarWorkflow::class)->tender($seminar->fresh(), now()->addMonth(), $coordination);

    expect(snSent($team->email, 'Neues Seminar zur Mitarbeit'))->toBeTrue()->and(snSent($applied->email, 'Neues Seminar zur Mitarbeit'))->toBeFalse()->and(snSent($muted->email, 'Neues Seminar zur Mitarbeit'))->toBeFalse();
});

it('reminds teamende once, three days before the deadline, who have not yet reported', function () {
    $open = snUser(RoleKey::Team);
    $applied = snUser(RoleKey::Team);
    $near = Seminar::factory()->status(SeminarStatus::Tendered)->create(['application_deadline' => now()->addDays(2)]);
    $far = Seminar::factory()->status(SeminarStatus::Tendered)->create(['title' => 'Später', 'application_deadline' => now()->addDays(20)]);
    SeminarApplication::query()->create(['seminar_id' => $near->id, 'user_id' => $applied->id]);

    $this->artisan('seminars:remind-deadlines', ['--dry-run' => true])->expectsOutputToContain('Fristerinnerungen: 1')->assertSuccessful();
    Mail::assertNothingSent();

    $this->artisan('seminars:remind-deadlines')->expectsOutputToContain('Fristerinnerungen: 1')->assertSuccessful();

    expect(snSent($open->email, 'Bewerbungsfrist endet bald'))->toBeTrue()->and(snSent($applied->email, 'Bewerbungsfrist endet bald'))->toBeFalse()->and($near->fresh()->tender_reminded_at)->not->toBeNull()->and($far->fresh()->tender_reminded_at)->toBeNull();

    Mail::fake();
    $this->artisan('seminars:remind-deadlines')->expectsOutputToContain('Fristerinnerungen: 0')->assertSuccessful();
    Mail::assertNothingSent();
});

/** Seminar mit bestätigtem Team: Anna (Leitung, Zusage), Ben (Warteliste). */
function snConfirmed(): array
{
    $seminar = Seminar::factory()->status(SeminarStatus::TeamConfirmed)->create(['contact_email' => 'lehrkraft@schule.example', 'team_target' => 1, 'application_deadline' => now()->addWeek()]);
    $seminar->sessions()->create(['starts_at' => now()->addMonth()]);
    $anna = snUser(RoleKey::Team);
    $ben = snUser(RoleKey::Team);
    SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'user_id' => $anna->id, 'decision' => 'confirm', 'outcome' => 'confirmed']);
    SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'user_id' => $ben->id, 'outcome' => 'waitlist', 'waitlist_position' => 1]);
    $seminar->forceFill(['lead_user_id' => $anna->id, 'team_confirmed_at' => now()])->save();

    return [$seminar->fresh(), $anna, $ben];
}

it('informs school and team about a cancellation and respects the team preference', function () {
    [$seminar, $anna, $ben] = snConfirmed();
    snOptOut($ben, 'seminar_team');

    app(SeminarWorkflow::class)->transition($seminar, SeminarStatus::Cancelled, snUser(RoleKey::Coordination), 'Schule hat abgesagt');

    expect(snSent('lehrkraft@schule.example', 'Seminar abgesagt'))->toBeTrue()->and(snSent($anna->email, 'Seminar abgesagt'))->toBeTrue()->and(snSent($ben->email, 'Seminar abgesagt'))->toBeFalse();
});

it('resets the team and asks again when a confirmed seminar gets a new date', function () {
    [$seminar, $anna] = snConfirmed();

    app(SeminarWorkflow::class)->transition($seminar, SeminarStatus::Planned, snUser(RoleKey::Coordination), 'Termin der Schule geändert');

    $seminar->refresh();

    expect($seminar->status)->toBe(SeminarStatus::Planned)->and($seminar->lead_user_id)->toBeNull()->and($seminar->team_confirmed_at)->toBeNull()->and($seminar->application_deadline)->toBeNull()
        ->and(SeminarApplication::query()->whereNotNull('outcome')->count())->toBe(0)->and(SeminarApplication::query()->count())->toBe(2)
        ->and(snSent($anna->email, 'Neuer Termin für'))->toBeTrue()->and(snSent('lehrkraft@schule.example', 'Neuer Termin für'))->toBeTrue();
});

it('invites the confirmed team to the onboarding, briefs the school and prepares the seminar', function () {
    [$seminar, $anna, $ben] = snConfirmed();
    $this->actingAs(snUser(RoleKey::Coordination));

    $this->post(route('seminars.plan.prepare', $seminar))->assertSessionHasErrors('team');
    $this->post(route('seminars.plan.onboarding', $seminar), ['onboarding_at' => now()->subDay()->format('Y-m-d H:i')])->assertSessionHasErrors('onboarding_at');
    $this->post(route('seminars.plan.onboarding', $seminar), ['onboarding_at' => now()->addWeeks(2)->format('Y-m-d H:i'), 'onboarding_link' => 'http://unsicher.example'])->assertSessionHasErrors('onboarding_link');
    $this->post(route('seminars.plan.onboarding', $seminar), ['onboarding_at' => now()->addWeeks(2)->format('Y-m-d H:i'), 'onboarding_link' => 'https://konferenz.example/raum'])->assertSessionHas('success');

    expect($seminar->fresh()->onboarding_at)->not->toBeNull()->and(snSent($anna->email, 'Einladung zum Onboarding'))->toBeTrue()->and(snSent($ben->email, 'Einladung zum Onboarding'))->toBeTrue();

    $this->post(route('seminars.plan.briefing', $seminar), ['text' => ''])->assertSessionHasErrors('text');
    $this->post(route('seminars.plan.briefing', $seminar), ['text' => 'Bitte einen Raum mit Beamer bereithalten.'])->assertSessionHas('success');
    Mail::assertSent(SeminarNoticeMail::class, fn (SeminarNoticeMail $mail): bool => $mail->hasTo('lehrkraft@schule.example') && in_array('Bitte einen Raum mit Beamer bereithalten.', $mail->paragraphs, true));

    $this->post(route('seminars.plan.prepare', $seminar))->assertSessionHas('success');
    expect($seminar->fresh()->status)->toBe(SeminarStatus::Prepared)->and($seminar->fresh()->briefing_sent_at)->not->toBeNull();
});

it('offers the new notification settings to the matching roles only', function () {
    $this->actingAs(snUser(RoleKey::Team))->get(route('account.notifications'))->assertOk()->assertSee('Neue Ausschreibungen und Fristerinnerungen')->assertSee('Hinweise zu Ihren Seminaren')->assertDontSee('Freigegebene Seminare');
    $this->actingAs(snUser(RoleKey::Coordination))->get(route('account.notifications'))->assertOk()->assertSee('Freigegebene Seminare');
    $this->actingAs(snUser(RoleKey::AdministrationStaff))->get(route('account.notifications'))->assertOk()->assertSee('Neue Seminaranfragen und Antworten der Schulen');
});
