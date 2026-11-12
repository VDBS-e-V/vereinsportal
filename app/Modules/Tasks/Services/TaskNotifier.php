<?php

namespace App\Modules\Tasks\Services;

use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use App\Modules\Access\Services\NotificationPreferences;
use App\Modules\Board\Models\BoardMeeting;
use App\Modules\Tasks\Mail\TaskAssignedMail;
use App\Modules\Tasks\Mail\TaskDueMail;
use App\Modules\Tasks\Models\Task;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * E-Mails des Aufgabensystems, nur an Personen, die sie in ihrem Konto eingeschaltet haben (Standard: aus):
 * „task_assigned“ (neue Zuweisung, sofort) und „task_due“ (Erinnerung am Tag vor und nach der Fälligkeit, einmal je Aufgabe und Person).
 * Wer sich selbst zuweist, bekommt keine Zuweisungsmail.
 */
final class TaskNotifier
{
    public function __construct(private readonly NotificationPreferences $preferences) {}

    /**
     * @param  array<int, Collection<int, Task>>  $tasksByUser  Konto-ID => neu zugewiesene Aufgaben
     */
    public function assigned(array $tasksByUser, User $by): int
    {
        $sent = 0;
        $users = User::query()->whereIn('id', array_keys($tasksByUser))->where('status', UserStatus::Active->value)->get()->keyBy('id');

        foreach ($this->preferences->filter($users->reject(fn (User $user): bool => $user->id === $by->id), 'task_assigned') as $user) {
            if (! $user->can('tasks.use')) {
                continue;
            }

            $tasks = $tasksByUser[$user->id]->each->loadMissing('project');

            try {
                Mail::to($user->email)->send(new TaskAssignedMail($tasks, BoardMeeting::attendeeName($by), route('tasks.mine')));
                $sent++;
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $sent;
    }

    /**
     * Fälligkeits-Erinnerungen versenden (oder bei $dryRun nur zählen).
     *
     * @return array{mails: int, tasks: int}
     */
    public function remind(bool $dryRun = false): array
    {
        $rows = DB::table('task_assignees as a')->join('tasks as t', 't.id', '=', 'a.task_id')
            ->whereNull('t.done_at')->whereNotNull('t.due_on')
            ->where(fn ($query) => $query
                ->where(fn ($soon) => $soon->whereNull('a.due_reminded_at')->whereDate('t.due_on', '>=', today())->whereDate('t.due_on', '<=', today()->addDay()))
                ->orWhere(fn ($late) => $late->whereNull('a.overdue_reminded_at')->whereDate('t.due_on', '<', today())))
            ->select('a.user_id', 'a.task_id')->get()->groupBy('user_id');

        $users = User::query()->whereIn('id', $rows->keys())->where('status', UserStatus::Active->value)->get();
        $mails = $tasksCount = 0;

        foreach ($this->preferences->filter($users, 'task_due') as $user) {
            if (! $user->can('tasks.use')) {
                continue;
            }

            $tasks = Task::query()->with('project')->whereIn('id', $rows[$user->id]->pluck('task_id'))->orderBy('due_on')->get();
            $overdue = $tasks->filter(fn (Task $task): bool => $task->due_on->lt(today()))->values();
            $soon = $tasks->reject(fn (Task $task): bool => $task->due_on->lt(today()))->values();

            if ($tasks->isEmpty()) {
                continue;
            }

            $tasksCount += $tasks->count();
            $mails++;

            if ($dryRun) {
                continue;
            }

            try {
                Mail::to($user->email)->send(new TaskDueMail($soon, $overdue, route('tasks.mine')));
            } catch (Throwable $exception) {
                report($exception);

                continue;
            }

            DB::table('task_assignees')->where('user_id', $user->id)->whereIn('task_id', $soon->pluck('id'))->update(['due_reminded_at' => now()]);
            DB::table('task_assignees')->where('user_id', $user->id)->whereIn('task_id', $overdue->pluck('id'))->update(['overdue_reminded_at' => now(), 'due_reminded_at' => now()]);
        }

        return ['mails' => $mails, 'tasks' => $tasksCount];
    }
}
