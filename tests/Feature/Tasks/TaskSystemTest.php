<?php

use App\Modules\Access\Models\User;
use App\Modules\Access\Services\NotificationPreferences;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\People\Models\Person;
use App\Modules\System\Services\SystemTasks;
use App\Modules\Tasks\Mail\TaskAssignedMail;
use App\Modules\Tasks\Mail\TaskDueMail;
use App\Modules\Tasks\Models\Task;
use App\Modules\Tasks\Models\TaskAttachment;
use App\Modules\Tasks\Models\TaskComment;
use App\Modules\Tasks\Models\TaskProject;
use App\Modules\Tasks\Models\TaskTemplate;
use App\Modules\Tasks\Services\TaskProjects;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Storage::fake('local');
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

    $this->get(route('tasks.projects.show', $project))->assertOk()->assertSee('data-vdbs-kanban', false)->assertSee('Login prüfen')->assertSee('Hans Helfer, Mia Leitung')->assertSee('Offen')->assertSee('In Arbeit')->assertSee('Erledigt');
    $this->get(route('tasks.projects.show', [$project, 'view' => 'list']))->assertOk()->assertDontSee('data-vdbs-kanban', false)->assertSee('Login prüfen')->assertSee('überfällig');
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

function tkFile(string $name, string $content): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'tk');
    file_put_contents($path, $content);

    return new UploadedFile($path, $name, null, null, true);
}

function tkTask(TaskProject $project, array $attributes = []): Task
{
    return Task::query()->create($attributes + ['task_project_id' => $project->id, 'task_status_id' => tkStatus($project, 'Offen')->id, 'title' => 'Aufgabe']);
}

const TK_PDF = "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n";

it('manages labels in the project settings and rejects duplicates', function () {
    $manager = tkUser(RoleKey::Coordination);
    $project = tkProject([], [$manager]);
    $this->actingAs($manager);
    $statuses = $project->statuses->mapWithKeys(fn ($s) => [$s->id => ['name' => $s->name, 'position' => $s->position] + ($s->is_done ? ['done' => 1] : [])])->all();
    $save = fn (array $extra) => $this->put(route('tasks.projects.update', $project), ['name' => 'Entwicklung', 'managers' => [$manager->id], 'statuses' => $statuses] + $extra);

    $save(['new_labels' => [['name' => 'Fehler', 'color' => '1'], ['name' => 'Wunsch', 'color' => 'gibtesnicht'], ['name' => '']]])->assertRedirect();
    expect($project->labels()->pluck('color', 'name')->all())->toBe(['Fehler' => '1', 'Wunsch' => 'gray']);

    $fehler = $project->labels()->where('name', 'Fehler')->first();
    $wunsch = $project->labels()->where('name', 'Wunsch')->first();
    $save(['labels' => [$fehler->id => ['name' => 'Wunsch', 'color' => '2'], $wunsch->id => ['name' => 'Fehler', 'color' => 'gray']]])->assertRedirect();
    expect($project->labels()->pluck('color', 'name')->all())->toBe(['Fehler' => 'gray', 'Wunsch' => '2']);

    $save(['labels' => [$fehler->id => ['name' => 'Gleich', 'color' => 'gray'], $wunsch->id => ['name' => 'gleich', 'color' => 'gray']]])->assertSessionHasErrors('labels');
    $save(['labels' => [$fehler->id => ['name' => 'Fehler', 'color' => 'gray', 'delete' => 1], $wunsch->id => ['name' => 'Wunsch', 'color' => '2']]])->assertRedirect();
    expect($project->labels()->pluck('name')->all())->toBe(['Wunsch']);
});

it('labels tasks, filters by label and applies labels in bulk', function () {
    $user = tkUser(RoleKey::Coordination);
    $project = tkProject([$user]);
    $bug = $project->labels()->create(['name' => 'Fehler', 'color' => '1']);
    $wish = $project->labels()->create(['name' => 'Wunsch', 'color' => '3']);
    $foreign = tkProject([$user], [], ['name' => 'Anderes'])->labels()->create(['name' => 'Fremd', 'color' => 'gray']);
    $this->actingAs($user);

    $this->post(route('tasks.store', $project), ['title' => 'Mit Label', 'labels_sent' => 1, 'labels' => [$bug->id, $foreign->id]])->assertRedirect();
    $this->post(route('tasks.store', $project), ['title' => 'Ohne Label']);
    $task = Task::query()->where('title', 'Mit Label')->firstOrFail();
    expect($task->labels->pluck('name')->all())->toBe(['Fehler']);

    $this->get(route('tasks.projects.show', [$project, 'label' => $bug->id]))->assertOk()->assertSee('Mit Label')->assertDontSee('Ohne Label');
    $this->get(route('tasks.projects.show', [$project, 'view' => 'list', 'label' => $bug->id]))->assertOk()->assertSee('Mit Label')->assertDontSee('Ohne Label')->assertSee('Fehler');

    $this->put(route('tasks.update', $task), ['title' => 'Mit Label', 'labels_sent' => 1, 'labels' => [$wish->id]]);
    expect($task->fresh()->labels->pluck('name')->all())->toBe(['Wunsch']);
    $this->put(route('tasks.update', $task), ['title' => 'Mit Label']);
    expect($task->fresh()->labels)->toHaveCount(1);
    $this->put(route('tasks.update', $task), ['title' => 'Mit Label', 'labels_sent' => 1]);
    expect($task->fresh()->labels)->toHaveCount(0);

    $ids = Task::query()->pluck('id')->all();
    $this->post(route('tasks.bulk', $project), ['ids' => $ids, 'action' => 'label', 'labels' => [$bug->id]])->assertSessionHas('success');
    expect(Task::query()->whereHas('labels')->count())->toBe(2);
    $this->post(route('tasks.bulk', $project), ['ids' => $ids, 'action' => 'label', 'labels' => [$foreign->id]])->assertSessionHasErrors('labels');
    $this->post(route('tasks.bulk', $project), ['ids' => $ids, 'action' => 'label'])->assertSessionHasErrors('labels');
});

it('keeps labels working when a task is created in a kanban column without a labels field', function () {
    $user = tkUser(RoleKey::Coordination);
    $project = tkProject([$user]);
    $this->actingAs($user)->post(route('tasks.store', $project), ['title' => 'Schnell', 'task_status_id' => tkStatus($project, 'Wartet')->id])->assertRedirect();

    expect(Task::query()->firstOrFail()->status->name)->toBe('Wartet');
});

it('manages subtasks with progress, several lines at once and permissions', function () {
    $user = tkUser(RoleKey::Coordination);
    $project = tkProject([$user]);
    $task = tkTask($project);
    $this->actingAs($user);

    $this->post(route('tasks.subtasks.store', $task), ['title' => "Erster Schritt\n\n  Zweiter Schritt  \nDritter Schritt"])->assertRedirect();
    $this->post(route('tasks.subtasks.store', $task), ['title' => ''])->assertSessionHasErrors('title');
    expect($task->subtasks()->pluck('title')->all())->toBe(['Erster Schritt', 'Zweiter Schritt', 'Dritter Schritt']);

    $first = $task->subtasks()->first();
    $this->post(route('tasks.subtasks.toggle', [$task, $first]))->assertRedirect();
    expect($first->fresh()->isDone())->toBeTrue();
    $this->get(route('tasks.show', $task))->assertOk()->assertSee('1/3')->assertSee('Zweiter Schritt');
    $this->get(route('tasks.projects.show', $project))->assertOk()->assertSee('1/3');
    $this->post(route('tasks.subtasks.toggle', [$task, $first]));
    expect($first->fresh()->isDone())->toBeFalse();

    $other = tkTask($project, ['title' => 'Andere']);
    $this->post(route('tasks.subtasks.toggle', [$other, $first]))->assertNotFound();
    $this->delete(route('tasks.subtasks.destroy', [$task, $first]))->assertRedirect();
    expect($task->subtasks()->count())->toBe(2);

    $this->actingAs(tkUser(RoleKey::Coordination));
    $this->post(route('tasks.subtasks.store', $task), ['title' => 'Fremd'])->assertForbidden();
    $this->post(route('tasks.subtasks.toggle', [$task, $task->subtasks()->first()]))->assertForbidden();
});

it('stores, downloads and deletes task attachments and removes the files with the task', function () {
    $user = tkUser(RoleKey::Coordination);
    $manager = tkUser(RoleKey::Employee);
    $project = tkProject([$user], [$manager]);
    $task = tkTask($project);
    $this->actingAs($user);

    $this->post(route('tasks.attachments.store', $task), ['title' => 'Konzept', 'files' => [tkFile('konzept.pdf', TK_PDF), tkFile('zahlen.txt', 'Zahlen')]])->assertRedirect()->assertSessionHas('success');
    $attachments = $task->attachments()->get();
    expect($attachments)->toHaveCount(2);
    Storage::disk('local')->assertExists($attachments[0]->path);

    $this->get(route('tasks.show', $task))->assertOk()->assertSee('Konzept');
    $this->get(route('tasks.attachments.download', [$task, $attachments[0]]))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    $this->get(route('tasks.attachments.download', [tkTask($project, ['title' => 'Andere']), $attachments[0]]))->assertNotFound();

    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
    $this->post(route('tasks.attachments.store', $task), ['files' => [tkFile('foto.png', $png)]])->assertSessionHasErrors('attachments');
    $this->post(route('tasks.attachments.store', $task), ['files' => [tkFile('falsch.pdf', 'kein pdf')]])->assertSessionHasErrors('attachments');
    $this->post(route('tasks.attachments.store', $task), [])->assertSessionHasErrors('files');

    // fremde Anhänge löscht nur, wer das Projekt verwaltet
    $this->actingAs($manager)->post(route('tasks.attachments.store', $task), ['files' => [tkFile('verwaltung.txt', 'Text der Verwaltung')]]);
    $this->actingAs($user)->post(route('tasks.attachments.destroy', $task), ['ids' => [$task->attachments()->where('original_name', 'verwaltung.txt')->value('id')]])->assertForbidden();
    $this->post(route('tasks.attachments.destroy', $task), ['ids' => [$attachments[1]->id]])->assertRedirect();
    expect($task->attachments()->count())->toBe(2);
    Storage::disk('local')->assertMissing($attachments[1]->path);

    $path = $attachments[0]->path;
    $this->actingAs($manager)->post(route('tasks.bulk', $project), ['ids' => [$task->id], 'action' => 'delete']);
    expect(TaskAttachment::query()->count())->toBe(0);
    Storage::disk('local')->assertMissing($path);
});

it('keeps task attachments from non-members', function () {
    $user = tkUser(RoleKey::Coordination);
    $project = tkProject([$user]);
    $task = tkTask($project);
    $this->actingAs($user)->post(route('tasks.attachments.store', $task), ['files' => [tkFile('a.txt', 'Beispieltext einer Datei')]]);
    $attachment = $task->attachments()->firstOrFail();

    $this->actingAs(tkUser(RoleKey::Coordination));
    $this->get(route('tasks.attachments.download', [$task, $attachment]))->assertForbidden();
    $this->post(route('tasks.attachments.store', $task), ['files' => [tkFile('b.txt', 'Zweiter Beispieltext')]])->assertForbidden();
    $this->post(route('tasks.attachments.destroy', $task), ['ids' => [$attachment->id]])->assertForbidden();
    expect($task->attachments()->count())->toBe(1);
});

it('creates, edits and uses task templates with labels, subtasks and a due date', function () {
    $manager = tkUser(RoleKey::Coordination);
    $member = tkUser(RoleKey::Employee);
    $project = tkProject([$member], [$manager]);
    $label = $project->labels()->create(['name' => 'Routine', 'color' => '2']);
    $this->actingAs($manager);

    $this->get(route('tasks.templates.index', $project))->assertOk();
    $this->post(route('tasks.templates.store', $project), ['name' => 'Monatsabschluss', 'title' => 'Monatsabschluss durchführen', 'description' => 'Alle Belege prüfen', 'priority' => 'high', 'due_in_days' => 7, 'subtasks' => "Belege sammeln\nBuchen\n\nBericht schreiben", 'labels' => [$label->id]])->assertRedirect(route('tasks.templates.index', $project));
    $this->post(route('tasks.templates.store', $project), ['name' => 'Monatsabschluss', 'title' => 'X', 'priority' => 'normal'])->assertSessionHasErrors('name');
    $this->post(route('tasks.templates.store', $project), ['name' => 'Y', 'title' => '', 'priority' => 'dringend'])->assertSessionHasErrors(['title', 'priority']);

    $template = TaskTemplate::query()->firstOrFail();
    expect($template->subtaskTitles())->toBe(['Belege sammeln', 'Buchen', 'Bericht schreiben'])->and($template->labels)->toHaveCount(1);
    $this->get(route('tasks.templates.edit', [$project, $template]))->assertOk()->assertSee('Monatsabschluss');
    $this->put(route('tasks.templates.update', [$project, $template]), ['name' => 'Monatsabschluss neu', 'title' => 'Monatsabschluss durchführen', 'priority' => 'high', 'due_in_days' => 5, 'subtasks' => 'Eins'])->assertRedirect();
    expect($template->fresh()->name)->toBe('Monatsabschluss neu')->and($template->fresh()->labels)->toHaveCount(0)->and($template->fresh()->subtaskTitles())->toBe(['Eins']);
    $this->put(route('tasks.templates.update', [$project, $template]), ['name' => 'Monatsabschluss neu', 'title' => 'Monatsabschluss durchführen', 'priority' => 'high', 'due_in_days' => 7, 'subtasks' => "Belege sammeln\nBuchen", 'labels' => [$label->id]]);

    // Mitglieder legen Aufgaben aus der Vorlage an, ändern sie aber nicht
    $this->actingAs($member);
    $this->get(route('tasks.projects.show', $project))->assertOk()->assertSee('Neue Aufgabe aus Vorlage');
    $this->post(route('tasks.templates.use', $project), ['template' => $template->id, 'assignees' => [$member->id]])->assertRedirect();

    $task = Task::query()->firstOrFail();
    expect($task->title)->toBe('Monatsabschluss durchführen')->and($task->priority)->toBe('high')->and($task->due_on->toDateString())->toBe(now()->addDays(7)->toDateString())
        ->and($task->subtasks->pluck('title')->all())->toBe(['Belege sammeln', 'Buchen'])->and($task->labels->pluck('name')->all())->toBe(['Routine'])->and($task->assignees)->toHaveCount(1);

    $this->post(route('tasks.templates.use', $project), ['template' => $template->id, 'title' => 'Eigener Titel', 'due_on' => '2030-01-15']);
    expect(Task::query()->where('title', 'Eigener Titel')->firstOrFail()->due_on->toDateString())->toBe('2030-01-15');
    $this->post(route('tasks.templates.use', $project), [])->assertSessionHasErrors('template');
    $this->post(route('tasks.templates.use', $project), ['template' => 9999])->assertSessionHasErrors('template');
    $this->post(route('tasks.templates.store', $project), ['name' => 'Heimlich', 'title' => 'X', 'priority' => 'normal'])->assertForbidden();
    $this->get(route('tasks.templates.edit', [$project, $template]))->assertForbidden();
    $this->post(route('tasks.templates.destroy-many', $project), ['ids' => [$template->id]])->assertForbidden();

    $this->actingAs($manager)->post(route('tasks.templates.destroy-many', $project), ['ids' => [$template->id]])->assertRedirect();
    expect(TaskTemplate::query()->count())->toBe(0)->and(Task::query()->count())->toBe(2);
});

it('saves a task as a template without assignees and due date', function () {
    $manager = tkUser(RoleKey::Coordination);
    $project = tkProject([], [$manager]);
    $label = $project->labels()->create(['name' => 'Routine', 'color' => '2']);
    $task = tkTask($project, ['title' => 'Quartalsbericht', 'description' => 'Zahlen holen', 'priority' => 'high', 'due_on' => now()->addDays(3)]);
    $task->labels()->sync([$label->id]);
    $task->subtasks()->create(['title' => 'Zahlen holen', 'position' => 1]);
    $task->assignees()->sync([$manager->id]);
    $this->actingAs($manager);

    $this->post(route('tasks.templates.from-task', $task), ['name' => ''])->assertSessionHasErrors('name');
    $this->post(route('tasks.templates.from-task', $task), ['name' => 'Quartal'])->assertRedirect(route('tasks.templates.index', $project));
    $this->post(route('tasks.templates.from-task', $task), ['name' => 'Quartal'])->assertSessionHasErrors('name');

    $template = TaskTemplate::query()->firstOrFail();
    expect($template->title)->toBe('Quartalsbericht')->and($template->priority)->toBe('high')->and($template->due_in_days)->toBeNull()->and($template->subtaskTitles())->toBe(['Zahlen holen'])->and($template->labels)->toHaveCount(1);

    $this->actingAs(tkUser(RoleKey::Employee));
    $this->post(route('tasks.templates.from-task', $task), ['name' => 'Heimlich'])->assertForbidden();
});

it('sends assignment mails only to people who switched them on', function () {
    Mail::fake();
    $actor = tkUser(RoleKey::Coordination);
    $wants = tkUser(RoleKey::Employee);
    $silent = tkUser(RoleKey::Employee);
    $project = tkProject([$wants, $silent], [$actor]);
    DB::table('notification_preferences')->insert(['user_id' => $wants->id, 'key' => 'task_assigned', 'enabled' => true, 'created_at' => now(), 'updated_at' => now()]);
    $this->actingAs($actor);

    $this->post(route('tasks.store', $project), ['title' => 'Neu', 'assignees' => [$wants->id, $silent->id, $actor->id]])->assertRedirect();
    Mail::assertSent(TaskAssignedMail::class, 1);
    Mail::assertSent(TaskAssignedMail::class, fn ($mail) => $mail->hasTo($wants->email) && $mail->tasks->first()->title === 'Neu');

    $task = Task::query()->firstOrFail();
    $this->put(route('tasks.update', $task), ['title' => 'Neu', 'assignees' => [$wants->id, $silent->id, $actor->id]]);
    Mail::assertSent(TaskAssignedMail::class, 1);

    DB::table('notification_preferences')->insert(['user_id' => $silent->id, 'key' => 'task_assigned', 'enabled' => true, 'created_at' => now(), 'updated_at' => now()]);
    $this->put(route('tasks.update', $task), ['title' => 'Neu', 'assignees' => [$wants->id, $silent->id, $actor->id]]);
    Mail::assertSent(TaskAssignedMail::class, 1);

    $second = tkTask($project, ['title' => 'Zwei']);
    $this->post(route('tasks.bulk', $project), ['ids' => [$task->id, $second->id], 'action' => 'assign', 'assignees' => [$wants->id]]);
    Mail::assertSent(TaskAssignedMail::class, fn ($mail) => $mail->hasTo($wants->email) && $mail->tasks->count() === 1);
});

it('lets each person decide about task mails and keeps them off by default', function () {
    $user = tkUser(RoleKey::Coordination);
    $prefs = app(NotificationPreferences::class);

    expect($prefs->allows($user, 'task_assigned'))->toBeFalse()->and($prefs->allows($user, 'task_due'))->toBeFalse()->and($prefs->allows($user, 'new_applications'))->toBeTrue()
        ->and($prefs->filter([$user], 'task_due'))->toBe([])->and($prefs->filter([$user], 'board_meetings'))->toHaveCount(1);

    $this->actingAs($user)->get(route('account.notifications'))->assertOk()->assertSee('Neue Aufgaben, die Ihnen zugewiesen werden')->assertSee('Erinnerung an fällige Aufgaben');
    $this->put(route('account.notifications.update'), ['notifications' => ['task_due']])->assertRedirect();

    expect($prefs->allows($user, 'task_due'))->toBeTrue()->and($prefs->allows($user, 'task_assigned'))->toBeFalse()->and($prefs->filter([$user], 'task_due'))->toHaveCount(1);

    // wer das Aufgabensystem nicht nutzt, sieht die Einträge nicht
    $this->actingAs(tkUser(RoleKey::Member))->get(route('account.notifications'))->assertDontSee('Erinnerung an fällige Aufgaben');
});

it('reminds opted-in people once about due and overdue tasks', function () {
    Mail::fake();
    $wants = tkUser(RoleKey::Coordination);
    $silent = tkUser(RoleKey::Coordination);
    $project = tkProject([$wants, $silent]);
    DB::table('notification_preferences')->insert(['user_id' => $wants->id, 'key' => 'task_due', 'enabled' => true, 'created_at' => now(), 'updated_at' => now()]);
    $make = function (string $title, $due, array $users, array $extra = []) use ($project) {
        $task = tkTask($project, ['title' => $title, 'due_on' => $due] + $extra);
        $task->assignees()->sync(array_map(fn ($u) => $u->id, $users));

        return $task;
    };
    $make('Heute', now(), [$wants, $silent]);
    $make('Morgen', now()->addDay(), [$wants]);
    $make('Gestern', now()->subDay(), [$wants]);
    $make('Später', now()->addDays(5), [$wants]);
    $make('Erledigt', now(), [$wants], ['done_at' => now(), 'task_status_id' => tkStatus($project, 'Erledigt')->id]);
    $make('Ohne Frist', null, [$wants]);

    $this->artisan('tasks:remind', ['--dry-run' => true])->expectsOutputToContain('1 Erinnerung(en) für 3 Aufgabe(n)')->assertSuccessful();
    Mail::assertNothingSent();

    $this->artisan('tasks:remind')->assertSuccessful();
    Mail::assertSent(TaskDueMail::class, 1);
    Mail::assertSent(TaskDueMail::class, fn ($mail) => $mail->hasTo($wants->email) && $mail->soon->pluck('title')->sort()->values()->all() === ['Heute', 'Morgen'] && $mail->overdue->pluck('title')->all() === ['Gestern']);

    $this->artisan('tasks:remind')->assertSuccessful();
    Mail::assertSent(TaskDueMail::class, 1);
});

it('lists the reminder command among the system tasks and registers it for the browser', function () {
    expect(array_key_exists('tasks:remind', Artisan::all()))->toBeTrue()
        ->and(collect(app(SystemTasks::class)->all())->pluck('command')->contains('tasks:remind'))->toBeTrue();
});

it('renders the kanban board with the design system components and without inline scripts', function () {
    $user = tkUser(RoleKey::Coordination);
    $project = tkProject([$user]);
    $task = tkTask($project, ['title' => 'Karte', 'priority' => 'high']);
    $html = $this->actingAs($user)->get(route('tasks.projects.show', $project))->assertOk()->getContent();

    expect($html)->toContain('vdbs-kanban')->toContain('vdbs-kanban-column')->toContain('vdbs-kanban-card--high')->toContain('data-move-url="'.route('tasks.move', $task).'"')->not->toContain('kanban.js')->not->toContain('kb-card');
});

it('deletes a project with its tasks and attachments', function () {
    $project = tkProject();
    $task = tkTask($project);
    $this->actingAs(tkUser(RoleKey::Administration))->post(route('tasks.attachments.store', $task), ['files' => [tkFile('a.txt', 'Beispieltext einer Datei')]]);
    $path = $task->attachments()->firstOrFail()->path;
    Storage::disk('local')->assertExists($path);

    $this->delete(route('tasks.projects.destroy', $project))->assertRedirect();

    Storage::disk('local')->assertMissing($path);
    expect(Task::query()->count())->toBe(0);
});
