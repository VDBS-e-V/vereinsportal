<?php

namespace App\Modules\Board\Models;

use App\Modules\Access\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Modul: Board. Eine Aufgabe aus einer Vorstandssitzung oder einem Beschluss, mit Zuständigen und Fälligkeit. */
class BoardTask extends Model
{
    protected $fillable = ['board_meeting_id', 'board_resolution_id', 'title', 'notes', 'due_on', 'done_at', 'created_by'];

    protected function casts(): array
    {
        return ['due_on' => 'date', 'done_at' => 'datetime'];
    }

    public function isDone(): bool
    {
        return $this->done_at !== null;
    }

    public function isOverdue(): bool
    {
        return ! $this->isDone() && $this->due_on !== null && $this->due_on->lt(today());
    }

    /** @return BelongsToMany<User, $this> */
    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'board_task_user', 'board_task_id', 'user_id');
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

    /** Namen der Zuständigen für Listen. */
    public function assigneeNames(): string
    {
        return $this->assignees->map(fn (User $user): string => BoardMeeting::attendeeName($user))->sort()->implode(', ');
    }
}
