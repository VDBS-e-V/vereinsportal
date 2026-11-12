<?php

namespace App\Modules\Board\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Board\Models\BoardMeeting;
use App\Modules\Board\Models\BoardResolution;
use App\Modules\Board\Models\BoardTask;
use App\Modules\Board\Services\BoardMembers;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Aufgaben des Vorstands: aus Sitzungen und Beschlüssen, mit Zuständigen (Mehrfachauswahl) und Fälligkeit; Sammelaktionen erledigen, öffnen, löschen. */
class BoardTaskController extends Controller
{
    public function __construct(private readonly BoardMembers $members) {}

    public function index(Request $request): View
    {
        $filters = $request->validate(['status' => ['nullable', Rule::in(['open', 'done', 'all'])], 'mine' => ['nullable', 'boolean'], 'overdue' => ['nullable', 'boolean']]);
        $status = $filters['status'] ?? 'open';

        $tasks = BoardTask::query()->with(['assignees.person', 'meeting', 'resolution'])
            ->when($status === 'open', fn ($query) => $query->whereNull('done_at'))
            ->when($status === 'done', fn ($query) => $query->whereNotNull('done_at'))
            ->when($request->boolean('mine'), fn ($query) => $query->whereHas('assignees', fn ($assignees) => $assignees->where('users.id', $this->user()->id)))
            ->when($request->boolean('overdue'), fn ($query) => $query->whereNull('done_at')->whereDate('due_on', '<', today()))
            ->orderByRaw('done_at is not null')->orderByRaw('due_on is null')->orderBy('due_on')->orderBy('id')->limit(500)->get();

        return view('board.tasks.index', [
            'tasks' => $tasks, 'status' => $status, 'mine' => $request->boolean('mine'), 'overdue' => $request->boolean('overdue'),
            'members' => $this->members->options(), 'meetings' => BoardMeeting::query()->orderByDesc('starts_at')->limit(50)->get()->mapWithKeys(fn (BoardMeeting $meeting): array => [$meeting->id => $meeting->title.' ('.$meeting->starts_at->format('d.m.Y').')'])->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $task = BoardTask::query()->create($data['attributes'] + ['created_by' => $this->user()->id]);
        $task->assignees()->sync($data['assignees']);

        AuditLog::record(AuditEvent::BoardTaskSaved, $this->user(), null, ['aufgabe' => $task->title]);

        $meeting = $task->board_meeting_id;

        return redirect()->to($meeting && $request->boolean('from_meeting') ? route('board.meetings.show', $meeting).'#tasks-heading' : route('board.tasks.index'))->with('success', 'Die Aufgabe wurde angelegt.');
    }

    public function edit(BoardTask $task): View
    {
        $task->load('assignees');

        return view('board.tasks.form', [
            'task' => $task, 'members' => $this->members->options($task->assignees->pluck('id')->all()),
            'resolutions' => $task->board_meeting_id ? BoardResolution::query()->where('board_meeting_id', $task->board_meeting_id)->orderBy('year')->orderBy('seq')->get()->mapWithKeys(fn (BoardResolution $resolution): array => [$resolution->id => $resolution->number().' '.$resolution->title])->all() : [],
        ]);
    }

    public function update(Request $request, BoardTask $task): RedirectResponse
    {
        $data = $this->validated($request, $task);
        $task->update($data['attributes']);
        $task->assignees()->sync($data['assignees']);

        AuditLog::record(AuditEvent::BoardTaskSaved, $this->user(), null, ['aufgabe' => $task->title]);

        return redirect()->route('board.tasks.index', ['status' => 'all'])->with('success', 'Die Aufgabe wurde gespeichert.');
    }

    /** Sammelaktion für ausgewählte Aufgaben: als erledigt markieren, wieder öffnen oder löschen. */
    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate(['ids' => ['required', 'array', 'min:1'], 'ids.*' => ['integer'], 'action' => ['required', Rule::in(['done', 'open', 'delete'])]], ['ids.required' => 'Bitte wählen Sie mindestens eine Aufgabe aus.']);
        $tasks = BoardTask::query()->whereIn('id', $data['ids'])->get();

        foreach ($tasks as $task) {
            if ($data['action'] === 'delete') {
                AuditLog::record(AuditEvent::BoardTaskDeleted, $this->user(), null, ['aufgabe' => $task->title]);
                $task->delete();
            } else {
                $task->forceFill(['done_at' => $data['action'] === 'done' ? ($task->done_at ?? now()) : null])->save();
                AuditLog::record(AuditEvent::BoardTaskSaved, $this->user(), null, ['aufgabe' => $task->title, 'stand' => $data['action'] === 'done' ? 'erledigt' : 'wieder offen']);
            }
        }

        $label = ['done' => 'als erledigt markiert', 'open' => 'wieder geöffnet', 'delete' => 'gelöscht'][$data['action']];

        return back()->with('success', $tasks->count() === 1 ? 'Die Aufgabe wurde '.$label.'.' : $tasks->count().' Aufgaben wurden '.$label.'.');
    }

    /**
     * @return array{attributes: array<string, mixed>, assignees: list<int>}
     */
    private function validated(Request $request, ?BoardTask $task = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'due_on' => ['nullable', 'date'],
            'board_meeting_id' => ['nullable', 'integer', Rule::exists('board_meetings', 'id')],
            'board_resolution_id' => ['nullable', 'integer', Rule::exists('board_resolutions', 'id')],
            'assignees' => ['nullable', 'array'], 'assignees.*' => ['integer'],
            'done' => ['nullable', 'boolean'],
        ], ['title.required' => 'Bitte beschreiben Sie die Aufgabe kurz.']);

        $meetingId = $data['board_meeting_id'] ?? $task?->board_meeting_id;
        $resolutionId = $data['board_resolution_id'] ?? null;

        if ($resolutionId !== null && BoardResolution::query()->whereKey($resolutionId)->when($meetingId, fn ($query) => $query->where('board_meeting_id', $meetingId))->doesntExist()) {
            throw ValidationException::withMessages(['board_resolution_id' => 'Der Beschluss gehört nicht zu dieser Sitzung.']);
        }

        $allowed = array_keys($this->members->options($task ? $task->assignees()->pluck('users.id')->all() : []));
        $assignees = array_values(array_unique(array_map('intval', $data['assignees'] ?? [])));

        if (array_diff($assignees, $allowed) !== []) {
            throw ValidationException::withMessages(['assignees' => 'Zuständig können nur Vorstandsmitglieder sein.']);
        }

        $attributes = ['title' => $data['title'], 'notes' => $data['notes'] ?? null, 'due_on' => $data['due_on'] ?? null, 'board_meeting_id' => $meetingId, 'board_resolution_id' => $resolutionId];

        if ($task !== null) {
            $attributes['done_at'] = $request->boolean('done') ? ($task->done_at ?? now()) : null;
        }

        return ['attributes' => $attributes, 'assignees' => $assignees];
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
