<?php

namespace App\Modules\Tasks\Models;

use App\Modules\Access\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modul: Tasks. Ein Projekt des Aufgabensystems mit Spalten (Status), Mitgliedern und Aufgaben. Mit role_key sind alle Konten mit dieser Rolle
 * automatisch Mitglieder und dürfen das Projekt verwalten (Projekt „Vorstand“: Rolle Vorstand).
 */
class TaskProject extends Model
{
    public const BOARD = 'vorstand';

    /** Voreingestellte Spalten: Name => erledigt? */
    public const DEFAULT_STATUSES = ['Offen' => false, 'In Arbeit' => false, 'Wartet' => false, 'Erledigt' => true];

    protected $fillable = ['slug', 'name', 'description', 'role_key', 'is_archived', 'created_by'];

    protected function casts(): array
    {
        return ['is_archived' => 'boolean'];
    }

    /** @return HasMany<TaskStatus, $this> */
    public function statuses(): HasMany
    {
        return $this->hasMany(TaskStatus::class, 'task_project_id')->orderBy('position')->orderBy('id');
    }

    /** @return HasMany<Task, $this> */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'task_project_id');
    }

    /** Ausdrücklich eingetragene Mitglieder (Rolle „manager“ oder „member“). @return BelongsToMany<User, $this> */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_project_members', 'task_project_id', 'user_id')->withPivot('role');
    }
}
