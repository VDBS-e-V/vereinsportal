<?php

namespace App\Modules\Tasks\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Support\Capabilities;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Board\Models\BoardMeeting;
use App\Modules\Tasks\Models\Task;
use App\Modules\Tasks\Models\TaskLabel;
use App\Modules\Tasks\Models\TaskProject;
use App\Modules\Tasks\Services\ProjectAccess;
use App\Modules\Tasks\Services\TaskProjects;
use App\Support\PortalAreas;
use App\Support\PortalNavigation;
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

    /** Die Projekte eines Bereichs (Menüpunkt „Aufgaben“ im Bereich): die, in denen die Person Mitglied ist, bzw. alle bei tasks.admin. */
    public function area(Request $request, string $area): View
    {
        $user = $this->user();
        $portalArea = PortalAreas::tryFrom($area);
        abort_unless($portalArea !== null && in_array($portalArea, PortalNavigation::taskAreas(), true) && PortalNavigation::canEnter($portalArea, $user), 403);
        $showArchived = $request->boolean('archived');

        // Das Projekt „Vorstand“ entsteht, sobald ein Vorstandsmitglied das Aufgabensystem im Bereich Vorstand öffnet.
        if ($portalArea === PortalAreas::Vorstand && in_array(RoleKey::BoardMember->value, Capabilities::activeRoleKeys($user), true)) {
            $this->projects->board($user);
        }

        $projects = $this->withCounts($this->access->visible($user)->where('area', $portalArea->value)->where('is_archived', $showArchived), $user)->orderBy('name')->get();

        return view('tasks.index', ['projects' => $projects, 'showArchived' => $showArchived, 'isAdmin' => $this->access->isAdmin($user), 'area' => $portalArea]);
    }

    /** Allgemeine Verwaltung: alle Projekte aller Bereiche (Geschäftsstelle, nur mit tasks.admin). */
    public function index(Request $request): View
    {
        $user = $this->user();
        $showArchived = $request->boolean('archived');

        if (in_array(RoleKey::BoardMember->value, Capabilities::activeRoleKeys($user), true)) {
            $this->projects->board($user);
        }

        $projects = $this->withCounts($this->access->visible($user)->where('is_archived', $showArchived), $user)->orderBy('area')->orderBy('name')->get();

        return view('tasks.admin', ['projects' => $projects, 'showArchived' => $showArchived]);
    }

    /** Mehrfachauswahl in der Verwaltung: archivieren, wieder öffnen oder löschen (das Projekt „Vorstand“ bleibt stehen). */
    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:200'], 'ids.*' => ['integer'],
            'aktion' => ['required', Rule::in(['archive', 'reopen', 'delete'])],
        ], ['ids.required' => 'Bitte wählen Sie mindestens ein Projekt aus.']);

        $done = 0;
        $kept = 0;

        foreach ($this->access->visible($this->user())->whereIn('id', $data['ids'])->get() as $project) {
            if ($project->slug === TaskProject::BOARD && $data['aktion'] !== 'reopen') {
                $kept++;

                continue;
            }

            if ($data['aktion'] === 'delete') {
                AuditLog::record(AuditEvent::TaskProjectDeleted, $this->user(), null, ['projekt' => $project->name, 'aufgaben' => $project->tasks()->count()]);
                $project->tasks()->get()->each->delete();
                $project->delete();
            } else {
                $project->update(['is_archived' => $data['aktion'] === 'archive']);
                AuditLog::record(AuditEvent::TaskProjectSaved, $this->user(), null, ['projekt' => $project->name, 'aktion' => $data['aktion'] === 'archive' ? 'archiviert' : 'wieder geöffnet']);
            }

            $done++;
        }

        $noun = $done === 1 ? 'Projekt' : 'Projekte';
        $message = match ($data['aktion']) {
            'delete' => $done.' '.$noun.' samt Aufgaben gelöscht.',
            'archive' => $done.' '.$noun.' archiviert.',
            default => $done.' '.$noun.' wieder geöffnet.',
        };

        return redirect()->route('tasks.index')->with('success', $message.($kept > 0 ? ' Das Projekt „Vorstand“ bleibt stehen.' : ''));
    }

    /** @param \Illuminate\Database\Eloquent\Builder<TaskProject> $query @return \Illuminate\Database\Eloquent\Builder<TaskProject> */
    private function withCounts($query, User $user)
    {
        return $query->withCount(['tasks as open_count' => fn ($tasks) => $tasks->whereNull('done_at'), 'tasks as overdue_count' => fn ($tasks) => $tasks->whereNull('done_at')->whereDate('due_on', '<', today()), 'tasks as mine_count' => fn ($tasks) => $tasks->whereNull('done_at')->whereHas('assignees', fn ($assignees) => $assignees->where('users.id', $user->id))]);
    }

    public function create(): View
    {
        abort_unless($this->access->isAdmin($this->user()), 403);

        return view('tasks.projects.create', ['roles' => $this->roleOptions(), 'users' => $this->allUsers(), 'areas' => $this->areaOptions()]);
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
            'isAdmin' => $this->access->isAdmin($this->user()), 'areas' => $this->areaOptions(),
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

        // Den Bereich eines Projekts ändert ebenfalls nur tasks.admin; das Vorstandsprojekt bleibt im Bereich Vorstand.
        if (! $this->access->isAdmin($this->user()) || $project->slug === TaskProject::BOARD) {
            unset($data['area']);
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

        return redirect()->route($this->access->isAdmin($this->user()) ? 'tasks.index' : 'tasks.area', $this->access->isAdmin($this->user()) ? [] : ['area' => $project->area])->with('success', $project->is_archived ? 'Das Projekt wurde archiviert.' : 'Das Projekt ist wieder aktiv.');
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
            'area' => ['nullable', Rule::in(array_map(fn (PortalAreas $area): string => $area->value, PortalNavigation::taskAreas()))],
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

    /** Bereiche, in denen ein Projekt erscheinen kann. @return array<string, string> */
    private function areaOptions(): array
    {
        return collect(PortalNavigation::taskAreas())->mapWithKeys(fn (PortalAreas $area): array => [$area->value => $area->label()])->all();
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
                ->where(fn ($active) => $active->where('status', UserStatus::Active->value)->whereHas('activeRoleAssignments', fn ($assignments) => $assignments->whereHas('role', fn ($role) => $role->whereIn('key', self::ROLES))))
                ->orWhereIn('id', $include))
            ->get()->mapWithKeys(fn (User $user): array => [$user->id => BoardMeeting::attendeeName($user)])->sort()->all();
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
