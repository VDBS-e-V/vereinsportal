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

it('keeps the hints off the public start page and shows them in the account and the matching area', function () {
    $this->get('/')->assertOk()->assertDontSee('Ihre Aufgaben');

    $coordination = toUser(RoleKey::Coordination);
    Seminar::factory()->create(['status' => SeminarStatus::Request]);

    // nicht mehr auf der Startseite, auch nicht für Angemeldete
    $this->actingAs($coordination)->get('/')->assertOk()->assertDontSee('Ihre Aufgaben')->assertDontSee('Neue Seminaranfragen');

    // im Konto alle Hinweise, im Bereich nur seine
    $this->get(route('account.home'))->assertOk()->assertSee('Ihre Aufgaben')->assertSee('Neue Seminaranfragen');
    $this->get(route('coordination.home'))->assertOk()->assertSee('Ihre Aufgaben')->assertSee('Neue Seminaranfragen');

    $this->actingAs(toUser())->get(route('account.home'))->assertOk()->assertSee('Im Moment gibt es nichts zu erledigen.');
});

it('assigns each hint to the area of the page it links to', function () {
    $user = toUser(RoleKey::AdministrationStaff);
    Seminar::factory()->create(['status' => SeminarStatus::Request]);

    $coordination = array_column(TaskOverview::forArea($user, \App\Support\PortalAreas::Koordination), 'label');
    $office = array_column(TaskOverview::forArea($user, \App\Support\PortalAreas::Geschaeftsstelle), 'label');

    expect($coordination)->toContain('Neue Seminaranfragen')->and($office)->not->toContain('Neue Seminaranfragen')
        ->and(TaskOverview::forArea($user, \App\Support\PortalAreas::Schulen))->toBe([]);
});

it('lists the own open and overdue tasks of the task system in the account and the area Aufgaben', function () {
    $user = toUser(RoleKey::Coordination);
    $project = app(\App\Modules\Tasks\Services\TaskProjects::class)->create(['name' => 'Entwicklung', 'description' => null, 'role_key' => null], [], [$user->id], toUser(RoleKey::Administration));
    $open = $project->statuses()->where('is_done', false)->first();
    $done = $project->statuses()->where('is_done', true)->first();

    foreach ([['Heute', now()->addDay(), $open, null], ['Gestern', now()->subDay(), $open, null], ['Fertig', now()->subDay(), $done, now()]] as [$title, $due, $status, $doneAt]) {
        $task = \App\Modules\Tasks\Models\Task::query()->create(['task_project_id' => $project->id, 'task_status_id' => $status->id, 'title' => $title, 'due_on' => $due, 'done_at' => $doneAt]);
        $task->assignees()->sync([$user->id]);
    }

    $labels = toLabels($user);
    expect($labels)->toContain('Offene Aufgaben im Aufgabensystem')->toContain('Überfällige Aufgaben im Aufgabensystem');

    $counts = collect(TaskOverview::for($user))->pluck('count', 'label');
    expect($counts['Offene Aufgaben im Aufgabensystem'])->toBe(2)->and($counts['Überfällige Aufgaben im Aufgabensystem'])->toBe(1);

    $this->actingAs($user);
    $this->get(route('account.home'))->assertOk()->assertSee('Offene Aufgaben im Aufgabensystem');
    $this->get(route('tasks.index'))->assertOk()->assertSee('Überfällige Aufgaben im Aufgabensystem');
    $this->get(route('coordination.home'))->assertOk()->assertDontSee('im Aufgabensystem');

    // wer das Aufgabensystem nicht nutzt, sieht die Hinweise nicht
    expect(toLabels(toUser(RoleKey::Team)))->not->toContain('Offene Aufgaben im Aufgabensystem');
});
