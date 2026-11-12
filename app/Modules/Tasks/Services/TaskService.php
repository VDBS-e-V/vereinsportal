<?php

namespace App\Modules\Tasks\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Tasks\Models\Task;
use App\Modules\Tasks\Models\TaskProject;
use App\Modules\Tasks\Models\TaskStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Aufgaben anlegen, ändern, verschieben (Kanban) und gesammelt bearbeiten. Der Erledigt-Zeitpunkt folgt der Spalte: In einer „erledigt“-Spalte ist die Aufgabe erledigt. */
final class TaskService
{
    public function __construct(private readonly TaskNotifier $notifier) {}

    /**
     * @param  array<string, mixed>  $data  title, description, priority, due_on, board_meeting_id, board_resolution_id
     * @param  list<int>  $assignees
     * @param  list<int>  $labelIds
     * @param  list<string>  $subtasks  Titel der Unteraufgaben
     */
    public function create(TaskProject $project, array $data, array $assignees, User $by, ?TaskStatus $status = null, array $labelIds = [], array $subtasks = []): Task
    {
        $task = DB::transaction(function () use ($project, $data, $assignees, $by, $status, $labelIds, $subtasks): Task {
            $status ??= $project->statuses()->where('is_done', false)->first() ?? $project->statuses()->firstOrFail();

            $task = Task::query()->create($this->attributes($data) + [
                'task_project_id' => $project->id, 'task_status_id' => $status->id, 'done_at' => $status->is_done ? now() : null,
                'position' => (int) Task::query()->where('task_status_id', $status->id)->max('position') + 1, 'created_by' => $by->id,
            ]);
            $task->assignees()->sync($assignees);
            $this->syncLabels($task, $labelIds);

            foreach (array_values($subtasks) as $position => $title) {
                $task->subtasks()->create(['title' => mb_substr(trim($title), 0, 200), 'position' => $position]);
            }

            AuditLog::record(AuditEvent::TaskSaved, $by, null, ['aufgabe' => $task->title, 'projekt' => $project->name]);

            return $task;
        });

        $this->notifier->assigned(collect($assignees)->mapWithKeys(fn (int $id): array => [$id => collect([$task])])->all(), $by);

        return $task;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $assignees
     * @param  list<int>|null  $labelIds  null = Labels unverändert lassen
     */
    public function update(Task $task, array $data, array $assignees, ?TaskStatus $status, User $by, ?array $labelIds = null): void
    {
        $before = $task->assignees()->pluck('users.id')->all();

        DB::transaction(function () use ($task, $data, $assignees, $status, $by, $labelIds): void {
            $task->update($this->attributes($data));
            $task->assignees()->sync($assignees);

            if ($labelIds !== null) {
                $this->syncLabels($task, $labelIds);
            }

            if ($status instanceof TaskStatus && $status->id !== $task->task_status_id) {
                $this->move($task, $status, null, $by, false);
            }

            AuditLog::record(AuditEvent::TaskSaved, $by, null, ['aufgabe' => $task->title, 'projekt' => $task->project->name]);
        });

        $this->notifier->assigned(collect(array_diff($assignees, $before))->mapWithKeys(fn (int $id): array => [$id => collect([$task])])->all(), $by);
    }

    /** Verschiebt eine Aufgabe in eine Spalte, an die Stelle $index (null = ans Ende), und nummeriert die Spalte neu. */
    public function move(Task $task, TaskStatus $status, ?int $index, User $by, bool $audit = true): void
    {
        DB::transaction(function () use ($task, $status, $index, $by, $audit): void {
            $others = Task::query()->where('task_status_id', $status->id)->where('id', '!=', $task->id)->orderBy('position')->orderBy('id')->pluck('id')->all();
            $at = $index === null ? count($others) : max(0, min($index, count($others)));
            array_splice($others, $at, 0, [$task->id]);

            $wasDone = $task->isDone();
            $task->forceFill(['task_status_id' => $status->id, 'done_at' => $status->is_done ? ($wasDone ? $task->done_at : now()) : null])->save();

            foreach ($others as $position => $id) {
                Task::query()->whereKey($id)->update(['position' => $position]);
            }

            if ($audit) {
                AuditLog::record(AuditEvent::TaskSaved, $by, null, ['aufgabe' => $task->title, 'projekt' => $task->project->name, 'spalte' => $status->name]);
            }
        });
    }

    /**
     * Sammelaktion für Aufgaben eines Projekts.
     *
     * @param  list<int>  $ids
     * @param  list<int>  $assignees  nur für „assign“: werden zu den bisherigen Zuständigen hinzugefügt
     */
    public function bulk(TaskProject $project, array $ids, string $action, ?TaskStatus $status, array $assignees, User $by): int
    {
        $tasks = Task::query()->where('task_project_id', $project->id)->whereIn('id', $ids)->get();
        /** @var array<int, Collection<int, Task>> $added */
        $added = [];

        foreach ($tasks as $task) {
            switch ($action) {
                case 'done':
                    $this->move($task, $project->statuses()->where('is_done', true)->firstOrFail(), null, $by);
                    break;
                case 'open':
                    $this->move($task, $project->statuses()->where('is_done', false)->firstOrFail(), null, $by);
                    break;
                case 'status':
                    $this->move($task, $status ?? $project->statuses()->firstOrFail(), null, $by);
                    break;
                case 'assign':
                    $existing = $task->assignees()->pluck('users.id')->all();
                    $task->assignees()->syncWithoutDetaching($assignees);

                    foreach (array_diff($assignees, $existing) as $userId) {
                        $added[$userId] = ($added[$userId] ?? collect())->push($task);
                    }
                    break;
                case 'label':
                    $task->labels()->syncWithoutDetaching($project->labels()->whereIn('id', $assignees)->pluck('id')->all());
                    break;
                case 'delete':
                    $this->delete($task, $by);
                    break;
            }
        }

        if ($added !== []) {
            $this->notifier->assigned($added, $by);
        }

        return $tasks->count();
    }

    public function delete(Task $task, User $by): void
    {
        AuditLog::record(AuditEvent::TaskDeleted, $by, null, ['aufgabe' => $task->title, 'projekt' => $task->project->name]);
        $task->delete();
    }

    /** @param  list<int>  $labelIds  nur Labels des Projekts der Aufgabe werden übernommen */
    public function syncLabels(Task $task, array $labelIds): void
    {
        $task->labels()->sync($task->project->labels()->whereIn('id', $labelIds)->pluck('id')->all());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        return [
            'title' => trim((string) $data['title']),
            'description' => filled($data['description'] ?? null) ? trim((string) $data['description']) : null,
            'priority' => $data['priority'] ?? 'normal',
            'due_on' => $data['due_on'] ?? null,
            'board_meeting_id' => $data['board_meeting_id'] ?? null,
            'board_resolution_id' => $data['board_resolution_id'] ?? null,
        ];
    }
}
