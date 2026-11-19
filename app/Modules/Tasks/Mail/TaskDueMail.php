<?php

namespace App\Modules\Tasks\Mail;

use App\Modules\MailBuilder\Mail\TemplatedMail;
use App\Modules\Tasks\Models\Task;
use Illuminate\Support\Collection;

/** Erinnerung an bald fällige und überfällige Aufgaben, eine Mail je Person und Lauf. Nur auf Wunsch der Person (Benachrichtigung „task_due“). */
final class TaskDueMail extends TemplatedMail
{
    /**
     * @param  Collection<int, Task>  $soon
     * @param  Collection<int, Task>  $overdue
     */
    public function __construct(public readonly Collection $soon, public readonly Collection $overdue, public readonly string $url) {}

    protected function systemKey(): string
    {
        return 'tasks.due';
    }

    protected function values(): array
    {
        $line = fn (Task $task, string $when): string => '<a href="'.e(route('tasks.show', $task)).'">'.e($task->title).'</a> (Projekt „'.e($task->project->name).'“, '.$when.' '.$task->due_on?->format('d.m.Y').')';

        return [
            'ueberfaellig' => $this->overdue->map(fn (Task $task): string => $line($task, 'fällig war der'))->values()->all(),
            'bald' => $this->soon->map(fn (Task $task): string => $line($task, 'fällig am'))->values()->all(),
            'url' => $this->url,
        ];
    }
}
