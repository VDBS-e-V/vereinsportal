<?php

namespace App\Modules\Tasks\Mail;

use App\Modules\MailBuilder\Mail\TemplatedMail;
use App\Modules\Tasks\Models\Task;
use Illuminate\Support\Collection;

/** Hinweis an eine Person: Ihr wurden Aufgaben zugewiesen. Nur auf Wunsch der Person (Benachrichtigung „task_assigned“). */
final class TaskAssignedMail extends TemplatedMail
{
    /** @param  Collection<int, Task>  $tasks */
    public function __construct(public readonly Collection $tasks, public readonly string $by, public readonly string $url) {}

    protected function systemKey(): string
    {
        return $this->tasks->count() === 1 ? 'tasks.assigned_one' : 'tasks.assigned_many';
    }

    protected function values(): array
    {
        return [
            'von' => $this->by,
            'titel' => (string) $this->tasks->first()?->title,
            'anzahl' => (string) $this->tasks->count(),
            'aufgaben' => $this->tasks->map(fn (Task $task): string => '<a href="'.e(route('tasks.show', $task)).'">'.e($task->title).'</a> (Projekt „'.e($task->project->name).'“'.($task->due_on ? ', fällig am '.$task->due_on->format('d.m.Y') : '').')')->values()->all(),
            'url' => $this->url,
        ];
    }
}
