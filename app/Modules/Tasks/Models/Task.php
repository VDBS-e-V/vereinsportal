<?php

namespace App\Modules\Tasks\Models;

use App\Modules\Access\Models\User;
use App\Modules\Board\Models\BoardMeeting;
use App\Modules\Board\Models\BoardResolution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/** Modul: Tasks. Eine Aufgabe in einem Projekt mit Status, Zuständigen, Priorität und Fälligkeit; optional mit Bezug zu einer Vorstandssitzung und einem Beschluss. */
class Task extends Model
{
    public const PRIORITIES = ['low' => 'Niedrig', 'normal' => 'Normal', 'high' => 'Hoch'];

    protected $fillable = ['task_project_id', 'task_status_id', 'title', 'description', 'priority', 'due_on', 'position', 'done_at', 'board_meeting_id', 'board_resolution_id', 'created_by'];

    protected function casts(): array
    {
        return ['due_on' => 'date', 'done_at' => 'datetime', 'position' => 'integer'];
    }

    protected static function booted(): void
    {
        // Die Dateien der Anhänge müssen mit gelöscht werden; die Datenbank allein ließe sie liegen.
        static::deleting(function (Task $task): void {
            $task->attachments()->get()->each->delete();
        });
    }

    public function isDone(): bool
    {
        return $this->done_at !== null;
    }

    public function isOverdue(): bool
    {
        return ! $this->isDone() && $this->due_on !== null && $this->due_on->lt(today());
    }

    public function priorityLabel(): string
    {
        return self::PRIORITIES[$this->priority] ?? $this->priority;
    }

    /** Namen der Zuständigen für Listen und Karten. */
    public function assigneeNames(): string
    {
        return $this->assignees->map(fn (User $user): string => BoardMeeting::attendeeName($user))->sort()->implode(', ');
    }

    /** @return BelongsTo<TaskProject, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(TaskProject::class, 'task_project_id');
    }

    /** @return BelongsTo<TaskStatus, $this> */
    public function status(): BelongsTo
    {
        return $this->belongsTo(TaskStatus::class, 'task_status_id');
    }

    /** @return BelongsToMany<User, $this> */
    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_assignees', 'task_id', 'user_id');
    }

    /** @return BelongsToMany<TaskLabel, $this> */
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(TaskLabel::class, 'task_label_task', 'task_id', 'task_label_id')->orderBy('name');
    }

    /** @return HasMany<TaskSubtask, $this> */
    public function subtasks(): HasMany
    {
        return $this->hasMany(TaskSubtask::class, 'task_id')->orderBy('position')->orderBy('id');
    }

    /** @return HasMany<TaskAttachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class, 'task_id')->orderBy('created_at');
    }

    /** Fortschritt der Unteraufgaben, zum Beispiel „2/5“; leer ohne Unteraufgaben. Setzt geladene Unteraufgaben voraus. */
    public function subtaskProgress(): string
    {
        /** @var Collection<int, TaskSubtask> $subtasks */
        $subtasks = $this->subtasks;

        return $subtasks->isEmpty() ? '' : $subtasks->whereNotNull('done_at')->count().'/'.$subtasks->count();
    }

    /** @return HasMany<TaskComment, $this> */
    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class, 'task_id')->orderBy('created_at')->orderBy('id');
    }

    /** @return BelongsTo<BoardMeeting, $this> */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(BoardMeeting::class, 'board_meeting_id');
    }

    /** @return BelongsTo<BoardResolution, $this> */
    public function resolution(): BelongsTo
    {
        return $this->belongsTo(BoardResolution::class, 'board_resolution_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
