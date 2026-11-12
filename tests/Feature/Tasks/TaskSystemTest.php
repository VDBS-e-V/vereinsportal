<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\People\Models\Person;
use App\Modules\Tasks\Models\Task;
use App\Modules\Tasks\Models\TaskComment;
use App\Modules\Tasks\Models\TaskProject;
use App\Modules\Tasks\Services\TaskProjects;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

function tkUser(?RoleKey $role, array $person = []): User
{
    $p = Person::factory()->create($person);
    $user = User::factory()->create(['person_id' => $p->id, 'email' => $p->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

/** Projekt mit den Standardspalten; Mitglieder und Verwaltende als Konten. */
function tkProject(array $members = [], array $managers = [], array $attributes = []): TaskProject
{
    return app(TaskProjects::class)->create($attributes + ['name' => 'Entwicklung', 'description' => null, 'role_key' => null], array_map(fn ($u) => $u->id, $managers), array_map(fn ($u) => $u->id, $members), tkUser(RoleKey::Administration));
}

function tkStatus(TaskProject $project, string $name)
{
    return $project->statuses()->where('name', $name)->firstOrFail();
}

it('lets administration create a project with the standard columns and members', function () {
    $admin = tkUser(RoleKey::Administration);
    $member = tkUser(RoleKey::Coordination);
    $this->actingAs($admin)->get(route('tasks.projects.create'))->assertOk();

    $this->post(route('tasks.projects.store'), ['name' => 'Portal-Entwicklung', 'description' => 'Wünsche und Fehler', 'members' => [$member->id], 'managers' => [$admin->id]])->assertRedirect();

    $project = TaskProject::query()->firstOrFail();
    expect($project->statuses->pluck('name')->all())->toBe(['Offen', 'In Arbeit', 'Wartet', 'Erledigt'])
        ->and($project->statuses->pluck('is_done')->all())->toBe([false, false, false, true])
        ->and($project->members)->toHaveCount(2)
        ->and(AuditEntry::query()->where('event', 'task.project_saved')->count())->toBe(1);

    $this->post(route('tasks.projects.store'), ['name' => ''])->assertSessionHasErrors('name');
    $this->post(route('tasks.projects.store'), ['name' => 'X', 'role_key' => 'guest'])->assertSessionHasErrors('role_key');
});

it('keeps project creation from everyone without tasks.admin', function () {
    foreach ([RoleKey::Coordination, RoleKey::AdministrationStaff, RoleKey::Employee] as $role) {
        $this->actingAs(tkUser($role));
        $this->get(route('tasks.projects.create'))->assertForbidden();
        $this->post(route('tasks.projects.store'), ['name' => 'Heimlich'])->assertForbidden();
    }

    foreach ([RoleKey::Team, RoleKey::Member, RoleKey::School, null] as $role) {
        $this->actingAs(tkUser($role));
        $this->get(route('tasks.index'))->assertForbidden();
    }

    expect(TaskProject::query()->count())->toBe(0);
});

it('shows each person only the projects they belong to', function () {
    $member = tkUser(RoleKey::Coordination);
    $other = tkUser(RoleKey::Coordination);
    $project = tkProject([$member]);
    $secret = tkProject([], [], ['name' => 'Geheimprojekt']);

    $this->actingAs($member)->get(route('tasks.index'))->assertOk()->assertSee('Entwicklung')->assertDontSee('Geheimprojekt');
    $this->get(route('tasks.projects.show', $project))->assertOk();
    $this->get(route('tasks.projects.show', $secret))->assertForbidden();

    $this->actingAs($other)->get(route('tasks.projects.show', $project))->assertForbidden();
    $this->actingAs(tkUser(RoleKey::Administration))->get(route('tasks.index'))->assertOk()->assertSee('Entwicklung')->assertSee('Geheimprojekt');
});

it('adds all holders of the project role automatically', function () {
    $project = tkProject([], [], ['name' => 'Koordinationsteam', 'role_key' => 'coordination']);

    $this->actingAs(tkUser(RoleKey::Coordination))->get(route('tasks.projects.show', $project))->assertOk();
    $this->actingAs(tkUser(RoleKey::Treasury))->get(route('tasks.projects.show', $project))->assertForbidden();
});

it('creates tasks with assignees, priority, due date and renders board and list', function () {
    $manager = tkUser(RoleKey::Coordination, ['first_name' => 'Mia', 'last_name' => 'Leitung']);
    $helper = tkUser(RoleKey::Employee, ['first_name' => 'Hans', 'last_name' => 'Helfer']);
    $outsider = tkUser(RoleKey::Treasury);
    $project = tkProject([$helper], [$manager]);
    $this->actingAs($manager);

    $this->post(route('tasks.store', $project), ['title' => 'Login prüfen', 'description' => 'Fehler bei Zwei-Faktor', 'priority' => 'high', 'due_on' => now()->subDay()->format('Y-m-d'), 'assignees' => [$manager->id, $helper->id]])->assertRedirect(route('tasks.projects.show', $project));
    $this->post(route('tasks.store', $project), ['title' => 'Fremd', 'assignees' => [$outsider->id]])->assertSessionHasErrors('assignees');
    $this->post(route('tasks.store', $project), ['title' => ''])->assertSessionHasErrors('title');
    $this->post(route('tasks.store', $project), ['title' => 'X', 'priority' => 'dringend'])->assertSessionHasErrors('priority');

    $task = Task::query()->firstOrFail();
    expect($task->status->name)->toBe('Offen')->and($task->priority)->toBe('high')->and($task->assignees)->toHaveCount(2)->and($task->isOverdue())->toBeTrue()
        ->and(AuditEntry::query()->where('event', 'task.saved')->count())->toBe(1);

    $this->get(route('tasks.projects.show', $project))->assertOk()->assertSee('data-kanban', false)->assertSee('Login prüfen')->assertSee('Hans Helfer, Mia Leitung')->assertSee('Offen')->assertSee('In Arbeit')->assertSee('Erledigt');
    $this->get(route('tasks.projects.show', [$project, 'view' => 'list']))->assertOk()->assertDontSee('data-kanban', false)->assertSee('Login prüfen')->assertSee('überfällig');
    $this->get(route('tasks.show', $task))->assertOk()->assertSee('Fehler bei Zwei-Faktor');
});

it('filters tasks by search, assignee, priority and overdue', function () {
    $user = tkUser(RoleKey::Coordination);
    $other = tkUser(RoleKey::Coordination);
    $project = tkProject([$user, $other]);
    $this->actingAs($user);
    $this->post(route('tasks.store', $project), ['title' => 'Alpha Aufgabe', 'assignees' => [$user->id], 'priority' => 'high']);
    $this->post(route('tasks.store', $project), ['title' => 'Beta Aufgabe', 'assignees' => [$other->id], 'due_on' => now()->subDay()->format('Y-m-d')]);
    $this->post(route('tasks.store', $project), ['title' => 'Gamma Aufgabe']);

    $show = fn (array $q) => $this->get(route('tasks.projects.show', [$project, 'view' => 'list'] + $q));
    $show(['q' => 'Alpha'])->assertSee('Alpha Aufgabe')->assertDontSee('Beta Aufgabe');
    $show(['assignee' => 'me'])->assertSee('Alpha Aufgabe')->assertDontSee('Beta Aufgabe');
    $show(['assignee' => (string) $other->id])->assertSee('Beta Aufgabe')->assertDontSee('Alpha Aufgabe');
    $show(['assignee' => 'none'])->assertSee('Gamma Aufgabe')->assertDontSee('Alpha Aufgabe');
    $show(['priority' => 'high'])->assertSee('Alpha Aufgabe')->assertDontSee('Gamma Aufgabe');
    $show(['overdue' => 1])->assertSee('Beta Aufgabe')->assertDontSee('Alpha Aufgabe');
});

it('moves tasks between columns and keeps the order', function () {
    $user = tkUser(RoleKey::Coordination);
    $project = tkProject([$user]);
    $this->actingAs($user);
    $a = Task::query()->create(['task_project_id' => $project->id, 'task_status_id' => tkStatus($project, 'Offen')->id, 'title' => 'A', 'position' => 0]);
    $b = Task::query()->create(['task_project_id' => $project->id, 'task_status_id' => tkStatus($project, 'Offen')->id, 'title' => 'B', 'position' => 1]);
    $c = Task::query()->create(['task_project_id' => $project->id, 'task_status_id' => tkStatus($project, 'In Arbeit')->id, 'title' => 'C', 'position' => 0]);
    $progress = tkStatus($project, 'In Arbeit');
    $done = tkStatus($project, 'Erledigt');

    $this->postJson(route('tasks.move', $a), ['status_id' => $progress->id, 'index' => 0])->assertOk()->assertJson(['ok' => true, 'done' => false]);
    expect(Task::query()->where('task_status_id', $progress->id)->orderBy('position')->pluck('title')->all())->toBe(['A', 'C'])
        ->and($b->fresh()->position)->toBe(1);

    $this->postJson(route('tasks.move', $a), ['status_id' => $done->id])->assertOk()->assertJson(['done' => true]);
    expect($a->fresh()->done_at)->not->toBeNull()->and($a->fresh()->isDone())->toBeTrue();

    $this->postJson(route('tasks.move', $a), ['status_id' => $progress->id, 'index' => 5])->assertOk();
    expect($a->fresh()->done_at)->toBeNull()->and(Task::query()->where('task_status_id', $progress->id)->orderBy('position')->pluck('title')->all())->toBe(['C', 'A']);

    $foreign = tkProject([$user], [], ['name' => 'Anderes']);
    $this->postJson(route('tasks.move', $a), ['status_id' => tkStatus($foreign, 'Offen')->id])->assertStatus(422);
    $this->post(route('tasks.move', $b), ['status_id' => $done->id])->assertRedirect()->assertSessionHas('success');
    expect($b->fresh()->isDone())->toBeTrue();
});

it('keeps non-members from touching tasks', function () {
    $member = tkUser(RoleKey::Coordination);
    $outsider = tkUser(RoleKey::Coordination);
    $project = tkProject([$member]);
    $task = Task::query()->create(['task_project_id' => $project->id, 'task_status_id' => tkStatus($project, 'Offen')->id, 'title' => 'Privat']);

    $this->actingAs($outsider);
    $this->get(route('tasks.show', $task))->assertForbidden();
    $this->put(route('tasks.update', $task), ['title' => 'Geändert'])->assertForbidden();
    $this->postJson(route('tasks.move', $task), ['status_id' => tkStatus($project, 'Erledigt')->id])->assertForbidden();
    $this->post(route('tasks.store', $project), ['title' => 'Neu'])->assertForbidden();
    $this->post(route('tasks.bulk', $project), ['ids' => [$task->id], 'action' => 'done'])->assertForbidden();
    $this->post(route('tasks.comments.store', $task), ['body' => 'Hallo'])->assertForbidden();
    $this->delete(route('tasks.destroy', $task))->assertForbidden();

    expect($task->fresh()->title)->toBe('Privat')->and($task->comments()->count())->toBe(0);
});

it('edits a task and changes its status in the detail view', function () {
    $user = tkUser(RoleKey::Coordination);
    $project = tkProject([$user]);
    $this->actingAs($user);
    $task = Task::query()->create(['task_project_id' => $project->id, 'task_status_id' => tkStatus($project, 'Offen')->id, 'title' => 'Alt']);

    $this->put(route('tasks.update', $task), ['title' => 'Neu', 'description' => 'Mit Text', 'priority' => 'low', 'assignees' => [$user->id], 'task_status_id' => tkStatus($project, 'Erledigt')->id])->assertRedirect(route('tasks.show', $task));

    $task->refresh();
    expect($task->title)->toBe('Neu')->and($task->priority)->toBe('low')->and($task->isDone())->toBeTrue()->and($task->status->name)->toBe('Erledigt')->and($task->assignees)->toHaveCount(1);

    $this->put(route('tasks.update', $task), ['title' => 'Neu', 'task_status_id' => tkStatus($project, 'Wartet')->id]);
    expect($task->fresh()->isDone())->toBeFalse()->and($task->fresh()->assignees)->toHaveCount(0);
});

it('handles bulk actions: done, reopen, move, assign and delete', function () {
    $manager = tkUser(RoleKey::Coordination);
    $member = tkUser(RoleKey::Employee);
    $outsider = tkUser(RoleKey::Treasury);
    $project = tkProject([$member], [$manager]);
    $tasks = collect(['A', 'B', 'C'])->map(fn ($title) => Task::query()->create(['task_project_id' => $project->id, 'task_status_id' => tkStatus($project, 'Offen')->id, 'title' => $title, 'created_by' => $manager->id]));
    $ids = $tasks->pluck('id')->all();

    $this->actingAs($member);
    $this->post(route('tasks.bulk', $project), ['ids' => $ids, 'action' => 'done'])->assertSessionHas('success');
    expect(Task::query()->whereNotNull('done_at')->count())->toBe(3);
    $this->post(route('tasks.bulk', $project), ['ids' => [$ids[0]], 'action' => 'open']);
    expect($tasks[0]->fresh()->isDone())->toBeFalse();
    $this->post(route('tasks.bulk', $project), ['ids' => $ids, 'action' => 'status', 'status_id' => tkStatus($project, 'Wartet')->id]);
    expect(Task::query()->where('task_status_id', tkStatus($project, 'Wartet')->id)->count())->toBe(3);
    $this->post(route('tasks.bulk', $project), ['ids' => $ids, 'action' => 'status'])->assertSessionHasErrors('status_id');

    $this->post(route('tasks.bulk', $project), ['ids' => $ids, 'action' => 'assign', 'assignees' => [$member->id]]);
    $this->post(route('tasks.bulk', $project), ['ids' => $ids, 'action' => 'assign', 'assignees' => [$manager->id]]);
    expect($tasks[1]->fresh()->assignees)->toHaveCount(2);
    $this->post(route('tasks.bulk', $project), ['ids' => $ids, 'action' => 'assign', 'assignees' => [$outsider->id]])->assertSessionHasErrors('assignees');
    $this->post(route('tasks.bulk', $project), ['ids' => $ids, 'action' => 'assign'])->assertSessionHasErrors('assignees');

    $this->post(route('tasks.bulk', $project), ['ids' => $ids, 'action' => 'delete'])->assertForbidden();
    $this->post(route('tasks.bulk', $project), ['ids' => $ids, 'action' => 'bogus'])->assertSessionHasErrors('action');
    $this->post(route('tasks.bulk', $project), [])->assertSessionHasErrors('ids');

    $this->actingAs($manager)->post(route('tasks.bulk', $project), ['ids' => $ids, 'action' => 'delete'])->assertSessionHas('success');
    expect(Task::query()->count())->toBe(0)->and(AuditEntry::query()->where('event', 'task.deleted')->count())->toBe(3);
});

it('lets the creator or a manager delete a single task', function () {
    $manager = tkUser(RoleKey::Coordination);
    $member = tkUser(RoleKey::Employee);
    $project = tkProject([$member], [$manager]);
    $own = Task::query()->create(['task_project_id' => $project->id, 'task_status_id' => tkStatus($project, 'Offen')->id, 'title' => 'Eigene', 'created_by' => $member->id]);
    $foreign = Task::query()->create(['task_project_id' => $project->id, 'task_status_id' => tkStatus($project, 'Offen')->id, 'title' => 'Fremde', 'created_by' => $manager->id]);

    $this->actingAs($member);
    $this->delete(route('tasks.destroy', $foreign))->assertForbidden();
    $this->delete(route('tasks.destroy', $own))->assertRedirect(route('tasks.projects.show', $project));
    $this->actingAs($manager)->delete(route('tasks.destroy', $foreign))->assertRedirect();

    expect(Task::query()->count())->toBe(0);
});

it('stores and deletes comments with the right permissions', function () {
    $manager = tkUser(RoleKey::Coordination);
    $member = tkUser(RoleKey::Employee, ['first_name' => 'Hans', 'last_name' => 'Helfer']);
    $project = tkProject([$member], [$manager]);
    $task = Task::query()->create(['task_project_id' => $project->id, 'task_status_id' => tkStatus($project, 'Offen')->id, 'title' => 'Mit Kommentar']);

    $this->actingAs($member);
    $this->post(route('tasks.comments.store', $task), ['body' => 'Ich übernehme das.'])->assertRedirect();
    $this->post(route('tasks.comments.store', $task), ['body' => ''])->assertSessionHasErrors('body');
    $this->get(route('tasks.show', $task))->assertOk()->assertSee('Ich übernehme das.')->assertSee('Hans Helfer');

    $comment = TaskComment::query()->firstOrFail();
    $this->actingAs(tkUser(RoleKey::Employee));
    $this->delete(route('tasks.comments.destroy', [$task, $comment]))->assertForbidden();

    $this->actingAs($member)->delete(route('tasks.comments.destroy', [$task, $comment]))->assertRedirect();
    expect(TaskComment::query()->count())->toBe(0);

    $this->actingAs($member)->post(route('tasks.comments.store', $task), ['body' => 'Zweiter']);
    $this->actingAs($manager)->delete(route('tasks.comments.destroy', [$task, TaskComment::query()->firstOrFail()]))->assertRedirect();
    expect(TaskComment::query()->count())->toBe(0);
});

it('edits columns: rename, reorder, add, mark as done and delete with tasks moving to the first column', function () {
    $manager = tkUser(RoleKey::Coordination);
    $project = tkProject([], [$manager]);
    $this->actingAs($manager)->get(route('tasks.projects.edit', $project))->assertOk()->assertSee('Spalten');
    $open = tkStatus($project, 'Offen');
    $wait = tkStatus($project, 'Wartet');
    $progress = tkStatus($project, 'In Arbeit');
    $done = tkStatus($project, 'Erledigt');
    $task = Task::query()->create(['task_project_id' => $project->id, 'task_status_id' => $wait->id, 'title' => 'Wartend']);

    $rows = fn (array $override = []) => [
        $open->id => ['name' => 'Backlog', 'position' => 0],
        $progress->id => ['name' => 'In Arbeit', 'position' => 1],
        $wait->id => ['name' => 'Wartet', 'position' => 2, 'delete' => 1],
        $done->id => ['name' => 'Fertig', 'position' => 3, 'done' => 1],
    ];

    $this->put(route('tasks.projects.update', $project), ['name' => 'Entwicklung', 'managers' => [$manager->id], 'statuses' => $rows(), 'new_statuses' => [['name' => 'Test', 'done' => 0], ['name' => '']]])->assertRedirect();

    $project->refresh();
    expect($project->statuses->pluck('name')->all())->toBe(['Backlog', 'In Arbeit', 'Fertig', 'Test'])
        ->and($task->fresh()->status->name)->toBe('Backlog')
        ->and($project->statuses->firstWhere('name', 'Fertig')->is_done)->toBeTrue();

    // ohne erledigt-Spalte und ohne Spalten nicht speichern
    $noDone = $project->statuses->mapWithKeys(fn ($status) => [$status->id => ['name' => $status->name, 'position' => $status->position]])->all();
    $this->put(route('tasks.projects.update', $project), ['name' => 'Entwicklung', 'managers' => [$manager->id], 'statuses' => $noDone])->assertSessionHasErrors('statuses');
    $all = $project->statuses->mapWithKeys(fn ($status) => [$status->id => ['name' => $status->name, 'position' => $status->position, 'delete' => 1]])->all();
    $this->put(route('tasks.projects.update', $project), ['name' => 'Entwicklung', 'managers' => [$manager->id], 'statuses' => $all])->assertSessionHasErrors('statuses');
    expect($project->fresh()->statuses)->toHaveCount(4);
});

it('marks tasks done when a column becomes a done column', function () {
    $manager = tkUser(RoleKey::Coordination);
    $project = tkProject([], [$manager]);
    $this->actingAs($manager);
    $wait = tkStatus($project, 'Wartet');
    $task = Task::query()->create(['task_project_id' => $project->id, 'task_status_id' => $wait->id, 'title' => 'Wartend']);

    $rows = $project->statuses->mapWithKeys(fn ($status) => [$status->id => ['name' => $status->name, 'position' => $status->position] + ($status->is_done || $status->id === $wait->id ? ['done' => 1] : [])])->all();
    $this->put(route('tasks.projects.update', $project), ['name' => 'Entwicklung', 'managers' => [$manager->id], 'statuses' => $rows])->assertRedirect();

    expect($task->fresh()->isDone())->toBeTrue();
});

it('lets only managers edit the project and only administration delete or change the role', function () {
    $manager = tkUser(RoleKey::Coordination);
    $member = tkUser(RoleKey::Employee);
    $project = tkProject([$member], [$manager]);

    $this->actingAs($member);
    $this->get(route('tasks.projects.edit', $project))->assertForbidden();
    $this->put(route('tasks.projects.update', $project), ['name' => 'Umbenannt'])->assertForbidden();
    $this->post(route('tasks.projects.archive', $project))->assertForbidden();

    $this->actingAs($manager);
    $this->put(route('tasks.projects.update', $project), ['name' => 'Umbenannt', 'role_key' => 'treasury', 'statuses' => $project->statuses->mapWithKeys(fn ($s) => [$s->id => ['name' => $s->name, 'position' => $s->position] + ($s->is_done ? ['done' => 1] : [])])->all(), 'managers' => [$manager->id]])->assertRedirect();
    expect($project->fresh()->name)->toBe('Umbenannt')->and($project->fresh()->role_key)->toBeNull();
    $this->delete(route('tasks.projects.destroy', $project))->assertForbidden();

    $this->post(route('tasks.projects.archive', $project))->assertRedirect(route('tasks.index'));
    expect($project->fresh()->is_archived)->toBeTrue();
    $this->get(route('tasks.index'))->assertDontSee('Umbenannt');
    $this->get(route('tasks.index', ['archived' => 1]))->assertSee('Umbenannt');

    $this->actingAs(tkUser(RoleKey::Administration))->delete(route('tasks.projects.destroy', $project))->assertRedirect(route('tasks.index'));
    expect(TaskProject::query()->count())->toBe(0);
});

it('protects the project Vorstand from archiving and deleting and gives it to the board automatically', function () {
    $board = tkUser(RoleKey::BoardMember);
    $this->actingAs($board)->get(route('tasks.index'))->assertOk()->assertSee('Vorstand');
    $project = TaskProject::query()->where('slug', 'vorstand')->firstOrFail();

    expect($project->role_key)->toBe('board_member');
    $this->get(route('tasks.projects.show', $project))->assertOk();
    $this->post(route('tasks.projects.archive', $project))->assertForbidden();
    $this->delete(route('tasks.projects.destroy', $project))->assertForbidden();

    $this->actingAs(tkUser(RoleKey::Coordination))->get(route('tasks.projects.show', $project))->assertForbidden();
});

it('lists my tasks across projects', function () {
    $me = tkUser(RoleKey::Coordination);
    $other = tkUser(RoleKey::Coordination);
    $one = tkProject([$me, $other], [], ['name' => 'Projekt Eins']);
    $two = tkProject([$other], [], ['name' => 'Projekt Zwei']);
    $make = function ($project, $title, $users, $extra = []) {
        $task = Task::query()->create($extra + ['task_project_id' => $project->id, 'task_status_id' => tkStatus($project, 'Offen')->id, 'title' => $title]);
        $task->assignees()->sync(array_map(fn ($u) => $u->id, $users));

        return $task;
    };
    $make($one, 'Meine offene', [$me], ['due_on' => now()->subDay()]);
    $make($one, 'Meine erledigte', [$me], ['task_status_id' => tkStatus($one, 'Erledigt')->id, 'done_at' => now()]);
    $make($one, 'Nicht meine', [$other]);
    $make($two, 'Anderes Projekt', [$me]);

    $this->actingAs($me)->get(route('tasks.mine'))->assertOk()->assertSee('Meine offene')->assertSee('Projekt Eins')->assertSee('überfällig')->assertDontSee('Meine erledigte')->assertDontSee('Nicht meine')->assertDontSee('Anderes Projekt');
    $this->get(route('tasks.mine', ['status' => 'all']))->assertSee('Meine erledigte');
    $this->get(route('tasks.mine', ['status' => 'done']))->assertSee('Meine erledigte')->assertDontSee('Meine offene');
});

it('shows the area Aufgaben to the roles that use the task system', function () {
    $this->actingAs(tkUser(RoleKey::Coordination))->get(route('tasks.index'))->assertOk()->assertSee('Meine Aufgaben');
    $this->actingAs(tkUser(RoleKey::Employee))->get(route('tasks.mine'))->assertOk();
});
