<?php

namespace App\Modules\Tasks\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Modul: Tasks. Ein Label eines Projekts zum Einordnen von Aufgaben; die Farbe kommt aus einer festen Auswahl (Design-Tokens). */
class TaskLabel extends Model
{
    /** Schlüssel => Bezeichnung. Die Farbe selbst steht im Design (Gruppen-Farben 1–6), nicht in der Datenbank. */
    public const COLORS = ['gray' => 'Grau', '1' => 'Farbe 1', '2' => 'Farbe 2', '3' => 'Farbe 3', '4' => 'Farbe 4', '5' => 'Farbe 5', '6' => 'Farbe 6'];

    protected $fillable = ['task_project_id', 'name', 'color'];

    /** @return BelongsTo<TaskProject, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(TaskProject::class, 'task_project_id');
    }

    /** @return BelongsToMany<Task, $this> */
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_label_task', 'task_label_id', 'task_id');
    }

    /** Gruppen-Nummer 1–6 für das Badge, null = neutral. */
    public function category(): ?int
    {
        return ctype_digit($this->color) ? (int) $this->color : null;
    }
}
