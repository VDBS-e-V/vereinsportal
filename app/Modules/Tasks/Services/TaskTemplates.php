<?php

namespace App\Modules\Tasks\Services;

use App\Modules\Access\Models\User;
use App\Modules\Tasks\Models\Task;
use App\Modules\Tasks\Models\TaskProject;
use App\Modules\Tasks\Models\TaskTemplate;
use Illuminate\Support\Facades\DB;

/** Aufgabenvorlagen eines Projekts: anlegen, ändern, aus einer Aufgabe ableiten und eine Aufgabe daraus erzeugen. */
final class TaskTemplates
{
    public function __construct(private readonly TaskService $tasks) {}

    /**
     * @param  array<string, mixed>  $data  name, title, description, priority, due_in_days, subtasks
     * @param  list<int>  $labelIds
     */
    public function save(TaskProject $project, ?TaskTemplate $template, array $data, array $labelIds): TaskTemplate
    {
        return DB::transaction(function () use ($project, $template, $data, $labelIds): TaskTemplate {
            $attributes = [
                'name' => trim((string) $data['name']), 'title' => trim((string) $data['title']), 'description' => filled($data['description'] ?? null) ? trim((string) $data['description']) : null,
                'priority' => $data['priority'] ?? 'normal', 'due_in_days' => isset($data['due_in_days']) && $data['due_in_days'] !== '' ? (int) $data['due_in_days'] : null,
                'subtasks' => filled($data['subtasks'] ?? null) ? trim((string) $data['subtasks']) : null,
            ];

            $template = $template instanceof TaskTemplate ? tap($template)->update($attributes) : $project->templates()->create($attributes);
            $template->labels()->sync($project->labels()->whereIn('id', $labelIds)->pluck('id')->all());

            return $template;
        });
    }

    /** Leitet eine Vorlage aus einer vorhandenen Aufgabe ab (ohne Zuständige und Fälligkeit). */
    public function fromTask(Task $task, string $name): TaskTemplate
    {
        $task->loadMissing(['labels', 'subtasks']);

        return $this->save($task->project, null, [
            'name' => $name, 'title' => $task->title, 'description' => $task->description, 'priority' => $task->priority,
            'subtasks' => $task->subtasks->pluck('title')->implode("\n"),
        ], $task->labels->pluck('id')->all());
    }

    /**
     * Erzeugt eine Aufgabe aus der Vorlage; Titel, Zuständige und Fälligkeit können abweichen.
     *
     * @param  array<string, mixed>  $overrides  title, due_on
     * @param  list<int>  $assignees
     */
    public function instantiate(TaskTemplate $template, array $overrides, array $assignees, User $by): Task
    {
        $dueOn = $overrides['due_on'] ?? ($template->due_in_days !== null ? now()->addDays($template->due_in_days)->toDateString() : null);

        return $this->tasks->create($template->project, [
            'title' => filled($overrides['title'] ?? null) ? $overrides['title'] : $template->title, 'description' => $template->description, 'priority' => $template->priority, 'due_on' => $dueOn,
        ], $assignees, $by, null, $template->labels()->pluck('task_labels.id')->all(), $template->subtaskTitles());
    }
}
