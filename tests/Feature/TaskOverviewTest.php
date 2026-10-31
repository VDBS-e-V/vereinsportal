<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Onboarding\Services\TeamOnboardings;
use App\Modules\People\Models\Person;
use App\Modules\Seminars\Enums\ApplicationStatus;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use App\Support\TaskOverview;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Mail::fake();
});

function toUser(?RoleKey $role = null): User
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

function toLabels(User $user): array
{
    return array_column(TaskOverview::for($user), 'label');
}

it('lists nothing for guests of any role and for accounts without tasks', function () {
    expect(TaskOverview::for(toUser()))->toBe([]);
});

it('shows Teamende their onboarding, profile and open seminars with counts only', function () {
    $team = toUser(RoleKey::Team);
    app(TeamOnboardings::class)->ensure($team);
    Seminar::factory()->create(['status' => SeminarStatus::Scheduled, 'team_needed' => 2, 'application_deadline' => now()->addWeek()->toDateString(), 'title' => 'Geheimer Titel']);
    $taken = Seminar::factory()->create(['status' => SeminarStatus::Scheduled, 'team_needed' => 2, 'application_deadline' => now()->addWeek()->toDateString()]);
    SeminarApplication::query()->create(['seminar_id' => $taken->id, 'user_id' => $team->id, 'status' => ApplicationStatus::Applied]);

    $tasks = TaskOverview::for($team);
    $byLabel = collect($tasks)->keyBy('label');

    expect($byLabel['Schritte Ihrer Einarbeitung sind offen']['count'])->toBe(4)
        ->and($byLabel['Seminare mit offenem Stichtag, für die Sie Interesse bekunden können']['count'])->toBe(1)
        ->and($byLabel)->toHaveKey('Profil für die Teamzusammenstellung ergänzen')
        ->and(json_encode($tasks))->not->toContain('Geheimer Titel');
});

it('shows the coordination seminar requests and teams waiting after the deadline', function () {
    $coordination = toUser(RoleKey::Coordination);
    Seminar::factory()->create(['status' => SeminarStatus::Request]);
    $waiting = Seminar::factory()->create(['status' => SeminarStatus::Scheduled, 'team_needed' => 2, 'application_deadline' => now()->subDay()->toDateString()]);
    SeminarApplication::query()->create(['seminar_id' => $waiting->id, 'user_id' => toUser(RoleKey::Team)->id, 'status' => ApplicationStatus::Applied]);

    $byLabel = collect(TaskOverview::for($coordination))->keyBy('label');

    expect($byLabel['Neue Seminaranfragen']['count'])->toBe(1)
        ->and($byLabel['Seminare, deren Team nach dem Stichtag noch zusammengestellt werden muss']['count'])->toBe(1);
});

it('shows the tasks on the start page for logged-in people only', function () {
    $this->get('/')->assertOk()->assertDontSee('Ihre Aufgaben');

    $coordination = toUser(RoleKey::Coordination);
    Seminar::factory()->create(['status' => SeminarStatus::Request]);

    $this->actingAs($coordination)->get('/')->assertOk()->assertSee('Ihre Aufgaben')->assertSee('Neue Seminaranfragen');
    $this->actingAs(toUser())->get('/')->assertOk()->assertSee('Im Moment gibt es nichts zu erledigen.');
});
