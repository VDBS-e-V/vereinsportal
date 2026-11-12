<?php

namespace App\Modules\Tasks\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Tasks\Models\Task;
use App\Modules\Tasks\Models\TaskProject;
use App\Modules\Tasks\Models\TaskStatus;
use Illuminate\Support\Facades\DB;

/** Aufgaben anlegen, ändern, verschieben (Kanban) und gesammelt bearbeiten. Der Erledigt-Zeitpunkt folgt der Spalte: In einer „erledigt“-Spalte ist die Aufgabe erledigt. */
final class TaskService
{
    /**
     * @param  array<string, mixed>  $data  title, description, priority, due_on, board_meeting_id, board_resolution_id
     * @param  list<int>  $assignees
     */
    public function create(TaskProject $project, array $data, array $assignees, User $by, ?TaskStatus $status = null): Task
    {
        return DB::transaction(function () use ($project, $data, $assignees, $by, $status): Task {
            $status ??= $project->statuses()->where('is_done', false)->first() ?? $project->statuses()->firstOrFail();

            $task = Task::query()->create($this->attributes($data) + [
                'task_project_id' => $project->id, 'task_status_id' => $status->id, 'done_at' => $status->is_done ? now() : null,
                'position' => (int) Task::query()->where('task_status_id', $status->id)->max('position') + 1, 'created_by' => $by->id,
            ]);
            $task->assignees()->sync($assignees);
            AuditLog::record(AuditEvent::TaskSaved, $by, null, ['aufgabe' => $task->title, 'projekt' => $project->name]);

            return $task;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $assignees
     */
    public function update(Task $task, array $data, array $assignees, ?TaskStatus $status, User $by): void
    {
        DB::transaction(function () use ($task, $data, $assignees, $status, $by): void {
            $task->update($this->attributes($data));
            $task->assignees()->sync($assignees);

            if ($status instanceof TaskStatus && $status->id !== $task->task_status_id) {
                $this->move($task, $status, null, $by, false);
            }

            AuditLog::record(AuditEvent::TaskSaved, $by, null, ['aufgabe' => $task->title, 'projekt' => $task->project->name]);
        });
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

        foreach ($tasks as $task) {
            match ($action) {
                'done' => $this->move($task, $project->statuses()->where('is_done', true)->firstOrFail(), null, $by),
                'open' => $this->move($task, $project->statuses()->where('is_done', false)->firstOrFail(), null, $by),
                'status' => $this->move($task, $status ?? $project->statuses()->firstOrFail(), null, $by),
                'assign' => $task->assignees()->syncWithoutDetaching($assignees),
                'delete' => $this->delete($task, $by),
                default => null,
            };
        }

        return $tasks->count();
    }

    public function delete(Task $task, User $by): void
    {
        AuditLog::record(AuditEvent::TaskDeleted, $by, null, ['aufgabe' => $task->title, 'projekt' => $task->project->name]);
        $task->delete();
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
