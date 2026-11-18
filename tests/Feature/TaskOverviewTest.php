<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\People\Models\Person;
use App\Modules\Tasks\Models\Task;
use App\Modules\Tasks\Services\TaskProjects;
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

it('lists the own open and overdue tasks of the task system in the account', function () {
    $user = toUser(RoleKey::Coordination);
    $project = app(TaskProjects::class)->create(['name' => 'Entwicklung', 'description' => null, 'role_key' => null], [], [$user->id], toUser(RoleKey::Administration));
    $open = $project->statuses()->where('is_done', false)->first();
    $done = $project->statuses()->where('is_done', true)->first();

    foreach ([['Heute', now()->addDay(), $open, null], ['Gestern', now()->subDay(), $open, null], ['Fertig', now()->subDay(), $done, now()]] as [$title, $due, $status, $doneAt]) {
        $task = Task::query()->create(['task_project_id' => $project->id, 'task_status_id' => $status->id, 'title' => $title, 'due_on' => $due, 'done_at' => $doneAt]);
        $task->assignees()->sync([$user->id]);
    }

    $labels = toLabels($user);
    expect($labels)->toContain('Offene Aufgaben im Aufgabensystem')->toContain('Überfällige Aufgaben im Aufgabensystem');

    $counts = collect(TaskOverview::for($user))->pluck('count', 'label');
    expect($counts['Offene Aufgaben im Aufgabensystem'])->toBe(2)->and($counts['Überfällige Aufgaben im Aufgabensystem'])->toBe(1);

    $this->actingAs($user);
    $this->get(route('account.home'))->assertOk()->assertSee('Offene Aufgaben im Aufgabensystem');
    $this->get(route('coordination.home'))->assertOk()->assertDontSee('im Aufgabensystem');

    // wer das Aufgabensystem nicht nutzt, sieht die Hinweise nicht
    expect(toLabels(toUser(RoleKey::Team)))->not->toContain('Offene Aufgaben im Aufgabensystem');
});
