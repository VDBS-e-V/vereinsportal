<?php

namespace App\Modules\Tasks\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Modul: Tasks. Eine Unteraufgabe (Teilschritt) einer Aufgabe mit Haken. */
class TaskSubtask extends Model
{
    protected $fillable = ['task_id', 'title', 'position', 'done_at'];

    protected function casts(): array
    {
        return ['done_at' => 'datetime', 'position' => 'integer'];
    }

    public function isDone(): bool
    {
        return $this->done_at !== null;
    }

    /** @return BelongsTo<Task, $this> */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }
}
