<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\Coordinations\Models\Coordination;
use App\Modules\Partners\Models\Partner;
use App\Modules\People\Models\Person;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use App\Modules\Seminars\Services\SeminarWorkflow;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

function plUser(?RoleKey $role = null): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

function plSessions(?string $date = null): array
{
    $date ??= now()->addMonths(2)->format('Y-m-d');

    return [['date' => $date, 'from' => '09:00', 'to' => '13:30', 'location' => 'Aula'], ['date' => '', 'from' => '', 'to' => '', 'location' => '']];
}

it('lets only the coordination see and plan released seminars', function () {
    $seminar = Seminar::factory()->status(SeminarStatus::Released)->create();
    $hidden = Seminar::factory()->status(SeminarStatus::InReview)->create(['title' => 'Noch in Prüfung']);

    $this->get(route('seminars.plan.index'))->assertRedirect(route('login'));
    $this->actingAs(plUser(RoleKey::AdministrationStaff))->get(route('seminars.plan.index'))->assertForbidden();

    $this->actingAs(plUser(RoleKey::Coordination));
    $this->get(route('seminars.plan.index'))->assertOk()->assertSee($seminar->title)->assertDontSee('Noch in Prüfung');
    $this->get(route('seminars.plan.show', $seminar))->assertOk()->assertSee('Termine und Team');
    $this->get(route('seminars.plan.show', $hidden))->assertNotFound();
});

it('plans a released seminar with sessions and team target and sets the school year', function () {
    $partner = Partner::factory()->create(['coordination_id' => Coordination::query()->create(['name' => 'Mitte'])->id]);
    $seminar = Seminar::factory()->status(SeminarStatus::Released)->create(['partner_id' => $partner->id]);
    $this->actingAs(plUser(RoleKey::Coordination));

    $this->post(route('seminars.plan.plan', $seminar), ['team_target' => '', 'sessions' => plSessions()])->assertSessionHasErrors('team_target');
    $this->post(route('seminars.plan.plan', $seminar), ['team_target' => 3, 'sessions' => []])->assertSessionHasErrors('plan');
    $this->post(route('seminars.plan.plan', $seminar), ['team_target' => 3, 'sessions' => [['date' => '2027-09-15', 'from' => 'früh', 'to' => '', 'location' => '']]])->assertSessionHasErrors('sessions.0.date');
    $this->post(route('seminars.plan.plan', $seminar), ['team_target' => 3, 'sessions' => [['date' => '2027-09-15', 'from' => '10:00', 'to' => '09:00', 'location' => '']]])->assertSessionHasErrors('sessions.0.to');

    $this->post(route('seminars.plan.plan', $seminar), ['team_target' => 3, 'sessions' => [['date' => '2027-09-16', 'from' => '09:00', 'to' => '12:00', 'location' => 'Aula'], ['date' => '2027-09-15', 'from' => '09:00', 'to' => '', 'location' => '']]])->assertRedirect()->assertSessionHas('success');

    $seminar->refresh();

    expect($seminar->status)->toBe(SeminarStatus::Planned)->and($seminar->team_target)->toBe(3)->and($seminar->school_year)->toBe('2027/28')->and($seminar->coordination_id)->toBe($partner->coordination_id)
        ->and($seminar->sessions)->toHaveCount(2)->and($seminar->sessions[0]->starts_at->format('Y-m-d'))->toBe('2027-09-15')
        ->and(AuditEntry::query()->where('event', 'seminars.planned')->count())->toBe(1);

    // ein geplantes Seminar lässt sich vor der Ausschreibung noch ändern
    $this->post(route('seminars.plan.plan', $seminar), ['team_target' => 4, 'sessions' => [['date' => '2027-09-20', 'from' => '08:00', 'to' => '', 'location' => '']]])->assertSessionHas('success');
    expect($seminar->fresh()->team_target)->toBe(4)->and($seminar->fresh()->sessions)->toHaveCount(1)->and($seminar->events()->where('type', 'plan_changed')->count())->toBe(1);
});

it('tenders a planned seminar with a deadline before the first session', function () {
    $seminar = Seminar::factory()->status(SeminarStatus::Released)->create();
    $this->actingAs(plUser(RoleKey::Coordination));

    $this->post(route('seminars.plan.tender', $seminar), ['application_deadline' => now()->addDay()->toDateString()])->assertSessionHasErrors('application_deadline');

    $this->post(route('seminars.plan.plan', $seminar), ['team_target' => 2, 'sessions' => plSessions(now()->addMonths(2)->format('Y-m-d'))]);
    $seminar->refresh();

    $this->post(route('seminars.plan.tender', $seminar), ['application_deadline' => ''])->assertSessionHasErrors('application_deadline');
    $this->post(route('seminars.plan.tender', $seminar), ['application_deadline' => now()->subDay()->toDateString()])->assertSessionHasErrors('application_deadline');
    $this->post(route('seminars.plan.tender', $seminar), ['application_deadline' => now()->addMonths(3)->toDateString()])->assertSessionHasErrors('application_deadline');
    expect($seminar->fresh()->status)->toBe(SeminarStatus::Planned);

    $this->post(route('seminars.plan.tender', $seminar), ['application_deadline' => now()->addMonth()->toDateString()])->assertSessionHas('success');

    expect($seminar->fresh()->status)->toBe(SeminarStatus::Tendered)->and($seminar->fresh()->application_deadline->toDateString())->toBe(now()->addMonth()->toDateString());

    // ausgeschrieben heißt: Termine nicht mehr über die Planung ändern
    $this->post(route('seminars.plan.plan', $seminar), ['team_target' => 5, 'sessions' => plSessions()])->assertSessionHasErrors('plan');
});

it('cancels a seminar only with a reason', function () {
    $seminar = Seminar::factory()->status(SeminarStatus::Planned)->create();
    $this->actingAs(plUser(RoleKey::Coordination));

    $this->post(route('seminars.plan.cancel', $seminar), ['comment' => ''])->assertSessionHasErrors('comment');
    $this->post(route('seminars.plan.cancel', $seminar), ['comment' => 'Schule hat abgesagt'])->assertSessionHas('success');

    expect($seminar->fresh()->status)->toBe(SeminarStatus::Cancelled)->and($seminar->events()->first()->comment)->toBe('Schule hat abgesagt');

    // neu ansetzen: zurück nach „Geplant“ mit Begründung
    expect(fn () => app(SeminarWorkflow::class)->transition($seminar->fresh(), SeminarStatus::Planned, plUser(RoleKey::Coordination)))->toThrow(DomainException::class, 'Begründung');
    app(SeminarWorkflow::class)->transition($seminar->fresh(), SeminarStatus::Planned, plUser(RoleKey::Coordination), 'Neuer Termin vereinbart');

    expect($seminar->fresh()->status)->toBe(SeminarStatus::Planned);
});

it('shows teamende the tendered seminars and lets them report and withdraw interest', function () {
    $seminar = Seminar::factory()->status(SeminarStatus::Tendered)->create(['title' => 'Medientag', 'application_deadline' => now()->addWeek()]);
    $seminar->sessions()->create(['starts_at' => now()->addMonth(), 'location' => 'Aula']);
    Seminar::factory()->status(SeminarStatus::Planned)->create(['title' => 'Noch nicht ausgeschrieben']);
    $team = plUser(RoleKey::Team);

    $this->get(route('seminars.team.index'))->assertRedirect(route('login'));
    $this->actingAs(plUser(RoleKey::Member))->get(route('seminars.team.index'))->assertForbidden();

    $this->actingAs($team)->get(route('seminars.team.index'))->assertOk()->assertSee('Medientag')->assertSee('Aula')->assertDontSee('Noch nicht ausgeschrieben');

    $this->post(route('seminars.team.apply', $seminar), ['availability' => 'Nur dienstags'])->assertSessionHas('success');
    $this->post(route('seminars.team.apply', $seminar), ['availability' => 'Dienstags und mittwochs'])->assertSessionHas('success');

    expect(SeminarApplication::query()->count())->toBe(1)->and(SeminarApplication::query()->first()->availability)->toBe('Dienstags und mittwochs');

    $this->get(route('seminars.team.index'))->assertSee('Interesse gemeldet');
    $this->actingAs(plUser(RoleKey::Coordination))->get(route('seminars.plan.show', $seminar))->assertSee('Dienstags und mittwochs');

    $this->actingAs($team)->delete(route('seminars.team.withdraw', $seminar))->assertSessionHas('success');
    expect(SeminarApplication::query()->first()->status)->toBe('withdrawn');
});

it('refuses interest after the deadline or for seminars that are not tendered', function () {
    $team = plUser(RoleKey::Team);
    $late = Seminar::factory()->status(SeminarStatus::Tendered)->create(['application_deadline' => now()->subDays(2)]);
    $planned = Seminar::factory()->status(SeminarStatus::Planned)->create();

    $this->actingAs($team)->post(route('seminars.team.apply', $late), ['availability' => 'immer'])->assertSessionHasErrors('apply');
    $this->post(route('seminars.team.apply', $planned), ['availability' => 'immer'])->assertSessionHasErrors('apply');

    expect(SeminarApplication::query()->count())->toBe(0);
});
