<?php

namespace App\Modules\Tasks\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Tasks\Models\Task;
use App\Modules\Tasks\Models\TaskLabel;
use App\Modules\Tasks\Models\TaskProject;
use App\Modules\Tasks\Services\ProjectAccess;
use App\Modules\Tasks\Services\TaskProjects;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/** Projekte des Aufgabensystems: Übersicht, Kanban- und Listenansicht, Einstellungen mit Mitgliedern und Spalten. */
class ProjectController extends Controller
{
    /** Rollen, die ein Projekt automatisch zu Mitgliedern machen kann. */
    private const ROLES = ['board_member', 'administration', 'administration_staff', 'coordination', 'education_coordination', 'managing_director', 'treasury', 'employee'];

    public function __construct(private readonly ProjectAccess $access, private readonly TaskProjects $projects) {}

    public function index(Request $request): View
    {
        $user = $this->user();
        $showArchived = $request->boolean('archived');

        // Das Projekt „Vorstand“ entsteht, sobald ein Vorstandsmitglied das Aufgabensystem öffnet.
        if (in_array(RoleKey::BoardMember->value, \App\Modules\AccessGovernance\Support\Capabilities::activeRoleKeys($user), true)) {
            $this->projects->board($user);
        }

        $projects = $this->access->visible($user)->where('is_archived', $showArchived)
            ->withCount(['tasks as open_count' => fn ($query) => $query->whereNull('done_at'), 'tasks as overdue_count' => fn ($query) => $query->whereNull('done_at')->whereDate('due_on', '<', today()), 'tasks as mine_count' => fn ($query) => $query->whereNull('done_at')->whereHas('assignees', fn ($assignees) => $assignees->where('users.id', $user->id))])
            ->orderBy('name')->get();

        return view('tasks.index', ['projects' => $projects, 'showArchived' => $showArchived, 'isAdmin' => $this->access->isAdmin($user), 'access' => $this->access]);
    }

    public function create(): View
    {
        abort_unless($this->access->isAdmin($this->user()), 403);

        return view('tasks.projects.create', ['roles' => $this->roleOptions(), 'users' => $this->allUsers()]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->access->isAdmin($this->user()), 403);
        $data = $this->validated($request);

        $project = $this->projects->create($data, $this->ids($data, 'managers'), $this->ids($data, 'members'), $this->user());

        return redirect()->route('tasks.projects.show', $project)->with('success', 'Das Projekt wurde angelegt.');
    }

    public function show(Request $request, TaskProject $project): View
    {
        $this->authorizeView($project);
        $user = $this->user();
        $filters = $request->validate([
            'view' => ['nullable', Rule::in(['board', 'list'])], 'q' => ['nullable', 'string', 'max:100'], 'assignee' => ['nullable', 'string', 'max:20'],
            'priority' => ['nullable', Rule::in(array_keys(Task::PRIORITIES))], 'label' => ['nullable', 'integer'], 'status' => ['nullable', Rule::in(['open', 'done', 'all'])],
        ]);
        $view = $filters['view'] ?? 'board';
        $assignee = $filters['assignee'] ?? '';

        $tasks = $project->tasks()->with(['assignees.person', 'status', 'meeting', 'labels', 'subtasks'])
            ->when($filters['label'] ?? null, fn ($query, $label) => $query->whereHas('labels', fn ($labels) => $labels->where('task_labels.id', $label)))
            ->when($filters['q'] ?? null, fn ($query, $q) => $query->where(fn ($inner) => $inner->where('title', 'like', '%'.addcslashes($q, '%_\\').'%')->orWhere('description', 'like', '%'.addcslashes($q, '%_\\').'%')))
            ->when($filters['priority'] ?? null, fn ($query, $priority) => $query->where('priority', $priority))
            ->when($assignee === 'me', fn ($query) => $query->whereHas('assignees', fn ($assignees) => $assignees->where('users.id', $user->id)))
            ->when($assignee === 'none', fn ($query) => $query->whereDoesntHave('assignees'))
            ->when(ctype_digit($assignee), fn ($query) => $query->whereHas('assignees', fn ($assignees) => $assignees->where('users.id', (int) $assignee)))
            ->when($request->boolean('overdue'), fn ($query) => $query->whereNull('done_at')->whereDate('due_on', '<', today()))
            ->when($view === 'list' && ($filters['status'] ?? 'open') === 'open', fn ($query) => $query->whereNull('done_at'))
            ->when($view === 'list' && ($filters['status'] ?? null) === 'done', fn ($query) => $query->whereNotNull('done_at'))
            ->orderBy('position')->orderBy('id')->get();

        return view('tasks.projects.show', [
            'project' => $project, 'statuses' => $project->statuses()->get(), 'tasks' => $tasks, 'view' => $view, 'filters' => $filters, 'overdue' => $request->boolean('overdue'),
            'assignable' => $this->access->assignable($project), 'labels' => $project->labels()->pluck('name', 'id')->all(), 'templates' => $project->templates()->pluck('name', 'id')->all(), 'canManage' => $this->access->canManage($user, $project), 'priorities' => Task::PRIORITIES,
        ]);
    }

    public function edit(TaskProject $project): View
    {
        $this->authorizeManage($project);
        $project->load('members');

        return view('tasks.projects.edit', [
            'project' => $project, 'statuses' => $project->statuses()->withCount('tasks')->get(), 'labelRows' => $project->labels()->withCount('tasks')->get(), 'colors' => TaskLabel::COLORS, 'roles' => $this->roleOptions(), 'users' => $this->allUsers($project->members->pluck('id')->all()),
            'managers' => $project->members->where('pivot.role', 'manager')->pluck('id')->all(), 'members' => $project->members->where('pivot.role', 'member')->pluck('id')->all(),
            'isAdmin' => $this->access->isAdmin($this->user()),
        ]);
    }

    public function update(Request $request, TaskProject $project): RedirectResponse
    {
        $this->authorizeManage($project);
        $data = $this->validated($request);

        // Die Rolle eines Projekts (automatische Mitglieder) ändert nur tasks.admin; das Vorstandsprojekt behält seine Rolle.
        if (! $this->access->isAdmin($this->user()) || $project->slug === TaskProject::BOARD) {
            $data['role_key'] = $project->role_key;
        }

        try {
            $this->projects->saveStatuses($project, (array) $request->input('statuses', []), (array) $request->input('new_statuses', []));
            $this->projects->saveLabels($project, (array) $request->input('labels', []), (array) $request->input('new_labels', []));
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors([str_contains($exception->getMessage(), 'Label') ? 'labels' : 'statuses' => $exception->getMessage()]);
        }

        $this->projects->update($project, $data, $this->ids($data, 'managers'), $this->ids($data, 'members'), $this->user());

        return redirect()->route('tasks.projects.show', $project)->with('success', 'Das Projekt wurde gespeichert.');
    }

    public function archive(TaskProject $project): RedirectResponse
    {
        $this->authorizeManage($project);
        abort_if($project->slug === TaskProject::BOARD, 403);
        $project->update(['is_archived' => ! $project->is_archived]);
        AuditLog::record(AuditEvent::TaskProjectSaved, $this->user(), null, ['projekt' => $project->name, 'aktion' => $project->is_archived ? 'archiviert' : 'wieder geöffnet']);

        return redirect()->route('tasks.index')->with('success', $project->is_archived ? 'Das Projekt wurde archiviert.' : 'Das Projekt ist wieder aktiv.');
    }

    public function destroy(TaskProject $project): RedirectResponse
    {
        abort_unless($this->access->isAdmin($this->user()) && $project->slug !== TaskProject::BOARD, 403);
        AuditLog::record(AuditEvent::TaskProjectDeleted, $this->user(), null, ['projekt' => $project->name, 'aufgaben' => $project->tasks()->count()]);
        $project->tasks()->get()->each->delete();
        $project->delete();

        return redirect()->route('tasks.index')->with('success', 'Das Projekt samt Aufgaben wurde gelöscht.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'role_key' => ['nullable', Rule::in(self::ROLES)],
            'managers' => ['nullable', 'array'], 'managers.*' => ['integer'],
            'members' => ['nullable', 'array'], 'members.*' => ['integer'],
        ], ['name.required' => 'Bitte geben Sie dem Projekt einen Namen.']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<int>
     */
    private function ids(array $data, string $key): array
    {
        return array_values(array_unique(array_map('intval', $data[$key] ?? [])));
    }

    /** @return array<string, string> */
    private function roleOptions(): array
    {
        return collect(self::ROLES)->mapWithKeys(fn (string $key): array => [$key => RoleKey::from($key)->label()])->all();
    }

    /** Aktive Konten mit einer Rolle, die das Aufgabensystem nutzt (plus bereits eingetragene Mitglieder). @return array<int, string> */
    private function allUsers(array $include = []): array
    {
        return User::query()->with('person')
            ->where(fn ($query) => $query
                ->where(fn ($active) => $active->where('status', \App\Modules\Access\Enums\UserStatus::Active->value)->whereHas('activeRoleAssignments', fn ($assignments) => $assignments->whereHas('role', fn ($role) => $role->whereIn('key', self::ROLES))))
                ->orWhereIn('id', $include))
            ->get()->mapWithKeys(fn (User $user): array => [$user->id => \App\Modules\Board\Models\BoardMeeting::attendeeName($user)])->sort()->all();
    }

    private function authorizeView(TaskProject $project): void
    {
        abort_unless($this->access->canView($this->user(), $project), 403);
    }

    private function authorizeManage(TaskProject $project): void
    {
        abort_unless($this->access->canManage($this->user(), $project), 403);
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
