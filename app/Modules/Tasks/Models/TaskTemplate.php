<?php

namespace App\Modules\Tasks\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Modul: Tasks. Eine Aufgabenvorlage eines Projekts: Titel, Beschreibung, Priorität, Frist in Tagen, Labels und Unteraufgaben (eine je Zeile). */
class TaskTemplate extends Model
{
    protected $fillable = ['task_project_id', 'name', 'title', 'description', 'priority', 'due_in_days', 'subtasks'];

    protected function casts(): array
    {
        return ['due_in_days' => 'integer'];
    }

    /** @return BelongsTo<TaskProject, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(TaskProject::class, 'task_project_id');
    }

    /** @return BelongsToMany<TaskLabel, $this> */
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(TaskLabel::class, 'task_template_label', 'task_template_id', 'task_label_id');
    }

    /** @return list<string> die Unteraufgaben aus dem Textfeld, eine je Zeile */
    public function subtaskTitles(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $this->subtasks) ?: []), fn (string $line): bool => $line !== ''));
    }
}
