<?php

namespace App\Modules\Tasks\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Board\Models\BoardResolution;
use App\Modules\Tasks\Models\Task;
use App\Modules\Tasks\Models\TaskComment;
use App\Modules\Tasks\Models\TaskProject;
use App\Modules\Tasks\Models\TaskStatus;
use App\Modules\Tasks\Services\ProjectAccess;
use App\Modules\Tasks\Services\TaskService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Aufgaben: anlegen, ansehen und bearbeiten, verschieben (Kanban), Sammelaktionen und Kommentare. Mitglieder eines Projekts bearbeiten, Verwaltende löschen fremde Aufgaben. */
class TaskController extends Controller
{
    public function __construct(private readonly ProjectAccess $access, private readonly TaskService $tasks) {}

    public function store(Request $request, TaskProject $project): RedirectResponse
    {
        $this->authorizeView($project);
        [$data, $assignees] = $this->validated($request, $project);

        $status = $request->filled('task_status_id') ? $project->statuses()->whereKey((int) $request->input('task_status_id'))->first() : null;
        $task = $this->tasks->create($project, $data, $assignees, $this->user(), $status);

        $target = $request->boolean('from_meeting') && $task->board_meeting_id !== null
            ? route('board.meetings.show', $task->board_meeting_id).'#tasks-heading'
            : route('tasks.projects.show', $project);

        return redirect()->to($target)->with('success', 'Die Aufgabe wurde angelegt.');
    }

    public function show(Task $task): View
    {
        $project = $task->project;
        $this->authorizeView($project);
        $task->load(['assignees.person', 'comments.author.person', 'status', 'meeting', 'resolution', 'creator.person']);

        return view('tasks.show', [
            'task' => $task, 'project' => $project, 'statuses' => $project->statuses()->get(), 'assignable' => $this->access->assignable($project, $task->assignees->pluck('id')->all()),
            'priorities' => Task::PRIORITIES, 'canManage' => $this->access->canManage($this->user(), $project), 'userId' => $this->user()->id,
            'resolutions' => $task->board_meeting_id ? BoardResolution::query()->where('board_meeting_id', $task->board_meeting_id)->orderBy('year')->orderBy('seq')->get()->mapWithKeys(fn (BoardResolution $resolution): array => [$resolution->id => $resolution->number().' '.$resolution->title])->all() : [],
        ]);
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $this->authorizeView($task->project);
        [$data, $assignees] = $this->validated($request, $task->project, $task);
        $status = $request->filled('task_status_id') ? $task->project->statuses()->whereKey((int) $request->input('task_status_id'))->first() : null;

        $this->tasks->update($task, $data, $assignees, $status, $this->user());

        return redirect()->route('tasks.show', $task)->with('success', 'Die Aufgabe wurde gespeichert.');
    }

    /** Kanban: Aufgabe in eine Spalte an eine Stelle verschieben. Antwortet mit JSON, auch für Formulare ohne JavaScript (dann mit Weiterleitung). */
    public function move(Request $request, Task $task): JsonResponse|RedirectResponse
    {
        $this->authorizeView($task->project);
        $data = $request->validate(['status_id' => ['required', 'integer'], 'index' => ['nullable', 'integer', 'min:0', 'max:10000']]);
        $status = $task->project->statuses()->whereKey($data['status_id'])->first();

        if (! $status instanceof TaskStatus) {
            throw ValidationException::withMessages(['status_id' => 'Diese Spalte gibt es in dem Projekt nicht.']);
        }

        $this->tasks->move($task, $status, isset($data['index']) ? (int) $data['index'] : null, $this->user());

        return $request->expectsJson() ? response()->json(['ok' => true, 'done' => $status->is_done]) : back()->with('success', 'Die Aufgabe liegt jetzt in „'.$status->name.'“.');
    }

    public function bulk(Request $request, TaskProject $project): RedirectResponse
    {
        $this->authorizeView($project);
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'], 'ids.*' => ['integer'], 'action' => ['required', Rule::in(['done', 'open', 'status', 'assign', 'delete'])],
            'status_id' => ['nullable', 'integer'], 'assignees' => ['nullable', 'array'], 'assignees.*' => ['integer'],
        ], ['ids.required' => 'Bitte wählen Sie mindestens eine Aufgabe aus.']);

        if ($data['action'] === 'delete') {
            abort_unless($this->access->canManage($this->user(), $project), 403);
        }

        $status = null;

        if ($data['action'] === 'status') {
            $status = $project->statuses()->whereKey((int) ($data['status_id'] ?? 0))->first();

            if (! $status instanceof TaskStatus) {
                throw ValidationException::withMessages(['status_id' => 'Bitte wählen Sie eine Spalte.']);
            }
        }

        $assignees = [];

        if ($data['action'] === 'assign') {
            $assignees = $this->checkedAssignees($project, $data['assignees'] ?? [], []);

            if ($assignees === []) {
                throw ValidationException::withMessages(['assignees' => 'Bitte wählen Sie, wem die Aufgaben zugewiesen werden.']);
            }
        }

        $count = $this->tasks->bulk($project, array_map('intval', $data['ids']), $data['action'], $status, $assignees, $this->user());
        $label = ['done' => 'als erledigt markiert', 'open' => 'wieder geöffnet', 'status' => 'verschoben', 'assign' => 'zugewiesen', 'delete' => 'gelöscht'][$data['action']];

        return back()->with('success', $count === 1 ? 'Die Aufgabe wurde '.$label.'.' : $count.' Aufgaben wurden '.$label.'.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $project = $task->project;
        $this->authorizeView($project);
        abort_unless($this->access->canManage($this->user(), $project) || $task->created_by === $this->user()->id, 403);

        $this->tasks->delete($task, $this->user());

        return redirect()->route('tasks.projects.show', $project)->with('success', 'Die Aufgabe wurde gelöscht.');
    }

    public function storeComment(Request $request, Task $task): RedirectResponse
    {
        $this->authorizeView($task->project);
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']], ['body.required' => 'Bitte schreiben Sie einen Kommentar.']);
        $task->comments()->create(['user_id' => $this->user()->id, 'body' => trim($data['body'])]);

        return redirect()->to(route('tasks.show', $task).'#comments-heading')->with('success', 'Der Kommentar wurde gespeichert.');
    }

    public function destroyComment(Task $task, TaskComment $comment): RedirectResponse
    {
        $this->authorizeView($task->project);
        abort_unless($comment->task_id === $task->id && ($comment->user_id === $this->user()->id || $this->access->canManage($this->user(), $task->project)), 403);
        $comment->delete();

        return redirect()->to(route('tasks.show', $task).'#comments-heading')->with('success', 'Der Kommentar wurde gelöscht.');
    }

    /**
     * @return array{0: array<string, mixed>, 1: list<int>}
     */
    private function validated(Request $request, TaskProject $project, ?Task $task = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'], 'description' => ['nullable', 'string', 'max:5000'],
            'priority' => ['nullable', Rule::in(array_keys(Task::PRIORITIES))], 'due_on' => ['nullable', 'date'],
            'assignees' => ['nullable', 'array'], 'assignees.*' => ['integer'],
            'board_meeting_id' => ['nullable', 'integer', Rule::exists('board_meetings', 'id')], 'board_resolution_id' => ['nullable', 'integer', Rule::exists('board_resolutions', 'id')],
        ], ['title.required' => 'Bitte beschreiben Sie die Aufgabe kurz.']);

        // Bezug zu Sitzung und Beschluss gibt es nur im Projekt „Vorstand“; der Beschluss muss zur Sitzung gehören.
        if ($project->slug !== TaskProject::BOARD) {
            $data['board_meeting_id'] = $data['board_resolution_id'] = null;
        } else {
            $data['board_meeting_id'] = $data['board_meeting_id'] ?? $task?->board_meeting_id;
            $resolution = $data['board_resolution_id'] ?? null;

            if ($resolution !== null && BoardResolution::query()->whereKey($resolution)->when($data['board_meeting_id'], fn ($query, $meeting) => $query->where('board_meeting_id', $meeting))->doesntExist()) {
                throw ValidationException::withMessages(['board_resolution_id' => 'Der Beschluss gehört nicht zu dieser Sitzung.']);
            }
        }

        return [$data, $this->checkedAssignees($project, $data['assignees'] ?? [], $task ? $task->assignees()->pluck('users.id')->all() : [])];
    }

    /**
     * Zuständig können nur Konten sein, die das Projekt sehen (bereits Zuständige bleiben erhalten).
     *
     * @param  array<int|string, mixed>  $ids
     * @param  list<int>  $current
     * @return list<int>
     */
    private function checkedAssignees(TaskProject $project, array $ids, array $current): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        if (array_diff($ids, array_keys($this->access->assignable($project, $current))) !== []) {
            throw ValidationException::withMessages(['assignees' => 'Zuständig können nur Mitglieder des Projekts sein.']);
        }

        return $ids;
    }

    private function authorizeView(TaskProject $project): void
    {
        abort_unless($this->access->canView($this->user(), $project), 403);
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
