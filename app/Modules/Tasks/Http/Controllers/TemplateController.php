<?php

namespace App\Modules\Tasks\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Tasks\Models\Task;
use App\Modules\Tasks\Models\TaskProject;
use App\Modules\Tasks\Models\TaskTemplate;
use App\Modules\Tasks\Services\ProjectAccess;
use App\Modules\Tasks\Services\TaskTemplates;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Aufgabenvorlagen: Verwaltende pflegen sie, alle Mitglieder legen daraus Aufgaben an. */
class TemplateController extends Controller
{
    public function __construct(private readonly ProjectAccess $access, private readonly TaskTemplates $templates) {}

    public function index(TaskProject $project): View
    {
        $this->authorizeView($project);

        return view('tasks.templates.index', [
            'project' => $project, 'templates' => $project->templates()->with('labels')->get(), 'labels' => $project->labels()->pluck('name', 'id')->all(), 'priorities' => Task::PRIORITIES,
            'canManage' => $this->access->canManage($this->user(), $project), 'assignable' => $this->access->assignable($project),
        ]);
    }

    public function store(Request $request, TaskProject $project): RedirectResponse
    {
        $this->authorizeManage($project);
        $data = $this->validated($request, $project);
        $this->templates->save($project, null, $data, $this->labelIds($data));

        return redirect()->route('tasks.templates.index', $project)->with('success', 'Die Vorlage wurde angelegt.');
    }

    public function edit(TaskProject $project, TaskTemplate $template): View
    {
        $this->authorizeManage($project);
        abort_unless($template->task_project_id === $project->id, 404);

        return view('tasks.templates.edit', ['project' => $project, 'template' => $template->load('labels'), 'labels' => $project->labels()->pluck('name', 'id')->all(), 'priorities' => Task::PRIORITIES]);
    }

    public function update(Request $request, TaskProject $project, TaskTemplate $template): RedirectResponse
    {
        $this->authorizeManage($project);
        abort_unless($template->task_project_id === $project->id, 404);
        $data = $this->validated($request, $project, $template);
        $this->templates->save($project, $template, $data, $this->labelIds($data));

        return redirect()->route('tasks.templates.index', $project)->with('success', 'Die Vorlage wurde gespeichert.');
    }

    public function destroyMany(Request $request, TaskProject $project): RedirectResponse
    {
        $this->authorizeManage($project);
        $ids = $request->validate(['ids' => ['required', 'array', 'min:1'], 'ids.*' => ['integer']], ['ids.required' => 'Bitte wählen Sie mindestens eine Vorlage aus.'])['ids'];
        $count = $project->templates()->whereIn('id', $ids)->delete();

        return redirect()->route('tasks.templates.index', $project)->with('success', $count === 1 ? 'Die Vorlage wurde gelöscht.' : $count.' Vorlagen wurden gelöscht.');
    }

    /** Legt aus der Vorlage eine Aufgabe an (optional mit eigenem Titel, Zuständigen und Fälligkeit). */
    public function use(Request $request, TaskProject $project): RedirectResponse
    {
        $this->authorizeView($project);
        $data = $request->validate(['template' => ['required', 'integer'], 'title' => ['nullable', 'string', 'max:200'], 'due_on' => ['nullable', 'date'], 'assignees' => ['nullable', 'array'], 'assignees.*' => ['integer']], ['template.required' => 'Bitte wählen Sie eine Vorlage.']);
        $template = $project->templates()->whereKey($data['template'])->first();

        if (! $template instanceof TaskTemplate) {
            throw ValidationException::withMessages(['template' => 'Diese Vorlage gibt es in dem Projekt nicht.']);
        }

        $assignees = array_values(array_unique(array_map('intval', $data['assignees'] ?? [])));

        if (array_diff($assignees, array_keys($this->access->assignable($project))) !== []) {
            throw ValidationException::withMessages(['assignees' => 'Zuständig können nur Mitglieder des Projekts sein.']);
        }

        $task = $this->templates->instantiate($template->load('project'), array_filter(['title' => $data['title'] ?? null, 'due_on' => $data['due_on'] ?? null], fn ($value) => $value !== null && $value !== ''), $assignees, $this->user());

        return redirect()->route('tasks.show', $task)->with('success', 'Die Aufgabe wurde aus der Vorlage „'.$template->name.'“ angelegt.');
    }

    /** Speichert eine Aufgabe als Vorlage (Titel, Beschreibung, Priorität, Labels, Unteraufgaben). */
    public function fromTask(Request $request, Task $task): RedirectResponse
    {
        $this->authorizeManage($task->project);
        $data = $request->validate(['name' => ['required', 'string', 'max:120', Rule::unique('task_templates', 'name')->where('task_project_id', $task->task_project_id)]], ['name.required' => 'Bitte geben Sie der Vorlage einen Namen.', 'name.unique' => 'Es gibt schon eine Vorlage mit diesem Namen.']);
        $this->templates->fromTask($task, $data['name']);

        return redirect()->route('tasks.templates.index', $task->project)->with('success', 'Die Vorlage wurde aus der Aufgabe erstellt.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, TaskProject $project, ?TaskTemplate $template = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('task_templates', 'name')->where('task_project_id', $project->id)->ignore($template?->id)],
            'title' => ['required', 'string', 'max:200'], 'description' => ['nullable', 'string', 'max:5000'],
            'priority' => ['required', Rule::in(array_keys(Task::PRIORITIES))], 'due_in_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'subtasks' => ['nullable', 'string', 'max:5000'], 'labels' => ['nullable', 'array'], 'labels.*' => ['integer'],
        ], ['name.required' => 'Bitte geben Sie der Vorlage einen Namen.', 'name.unique' => 'Es gibt schon eine Vorlage mit diesem Namen.', 'title.required' => 'Bitte geben Sie den Titel der Aufgabe an.']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<int>
     */
    private function labelIds(array $data): array
    {
        return array_values(array_unique(array_map('intval', $data['labels'] ?? [])));
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
