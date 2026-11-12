<?php

namespace App\Modules\Tasks\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Modul: Tasks. Eine Spalte (Status) eines Projekts; is_done zählt die Aufgaben darin als erledigt. */
class TaskStatus extends Model
{
    protected $fillable = ['task_project_id', 'name', 'position', 'is_done'];

    protected function casts(): array
    {
        return ['is_done' => 'boolean', 'position' => 'integer'];
    }

    /** @return BelongsTo<TaskProject, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(TaskProject::class, 'task_project_id');
    }

    /** @return HasMany<Task, $this> */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'task_status_id')->orderBy('position')->orderBy('id');
    }
}
