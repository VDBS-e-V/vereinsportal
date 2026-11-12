<?php

namespace App\Modules\Tasks\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Tasks\Models\Task;
use App\Modules\Tasks\Models\TaskLabel;
use App\Modules\Tasks\Models\TaskProject;
use App\Modules\Tasks\Models\TaskStatus;
use DomainException;
use Illuminate\Support\Facades\DB;

/** Projekte und ihre Spalten anlegen und pflegen. Spalten werden nie gelöscht, solange Aufgaben darin liegen: Diese ziehen in die erste verbleibende Spalte um. */
final class TaskProjects
{
    /**
     * @param  array<string, mixed>  $data  name, description, role_key
     * @param  list<int>  $managers
     * @param  list<int>  $members
     */
    public function create(array $data, array $managers, array $members, User $by): TaskProject
    {
        return DB::transaction(function () use ($data, $managers, $members, $by): TaskProject {
            $project = TaskProject::query()->create($this->attributes($data) + ['created_by' => $by->id]);
            $this->createDefaultStatuses($project);
            $this->syncMembers($project, $managers, $members);
            AuditLog::record(AuditEvent::TaskProjectSaved, $by, null, ['projekt' => $project->name]);

            return $project;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $managers
     * @param  list<int>  $members
     */
    public function update(TaskProject $project, array $data, array $managers, array $members, User $by): void
    {
        $project->update($this->attributes($data));
        $this->syncMembers($project, $managers, $members);
        AuditLog::record(AuditEvent::TaskProjectSaved, $by, null, ['projekt' => $project->name]);
    }

    /** Das Projekt „Vorstand“ wird beim ersten Bedarf angelegt; die Rolle Vorstand ist automatisch Mitglied. */
    public function board(?User $by = null): TaskProject
    {
        $project = TaskProject::query()->where('slug', TaskProject::BOARD)->first();

        if ($project instanceof TaskProject) {
            return $project;
        }

        $project = TaskProject::query()->create(['slug' => TaskProject::BOARD, 'name' => 'Vorstand', 'description' => 'Aufgaben aus den Vorstandssitzungen und Beschlüssen.', 'role_key' => 'board_member', 'created_by' => $by?->id]);
        $this->createDefaultStatuses($project);

        return $project;
    }

    /**
     * Spalten speichern: bestehende umbenennen, sortieren, als „erledigt“ kennzeichnen oder löschen; neue anlegen.
     *
     * @param  array<int|string, array{name?: string, position?: int|string, done?: mixed, delete?: mixed}>  $rows  Schlüssel = Status-ID
     * @param  list<array{name?: string, done?: mixed}>  $new
     *
     * @throws DomainException
     */
    public function saveStatuses(TaskProject $project, array $rows, array $new): void
    {
        DB::transaction(function () use ($project, $rows, $new): void {
            $existing = $project->statuses()->get()->keyBy('id');
            $keep = [];
            $delete = [];

            foreach ($existing as $id => $status) {
                $row = $rows[$id] ?? null;

                if ($row === null || ! empty($row['delete'])) {
                    $delete[] = $status;

                    continue;
                }

                $name = trim((string) ($row['name'] ?? ''));

                if ($name === '') {
                    throw new DomainException('Jede Spalte braucht einen Namen.');
                }

                $keep[] = ['model' => $status, 'name' => $name, 'position' => (int) ($row['position'] ?? 0), 'done' => ! empty($row['done'])];
            }

            foreach ($new as $row) {
                $name = trim((string) ($row['name'] ?? ''));

                if ($name !== '') {
                    $keep[] = ['model' => null, 'name' => $name, 'position' => 1000 + count($keep), 'done' => ! empty($row['done'])];
                }
            }

            if ($keep === []) {
                throw new DomainException('Ein Projekt braucht mindestens eine Spalte.');
            }

            if (collect($keep)->where('done', true)->isEmpty()) {
                throw new DomainException('Mindestens eine Spalte muss als „erledigt“ gelten, sonst lässt sich keine Aufgabe abschließen.');
            }

            usort($keep, fn (array $a, array $b): int => $a['position'] <=> $b['position']);

            $saved = [];

            foreach ($keep as $index => $entry) {
                $attributes = ['name' => $entry['name'], 'position' => $index, 'is_done' => $entry['done']];
                $saved[] = $entry['model'] instanceof TaskStatus ? tap($entry['model'])->update($attributes) : $project->statuses()->create($attributes);
            }

            $target = $saved[0];

            foreach ($delete as $status) {
                Task::query()->where('task_status_id', $status->id)->update(['task_status_id' => $target->id, 'done_at' => $target->is_done ? now() : null]);
                $status->delete();
            }

            // Erledigt-Zeitpunkt an die (geänderte) Kennzeichnung der Spalten angleichen.
            foreach ($saved as $status) {
                Task::query()->where('task_status_id', $status->id)->when(
                    $status->is_done,
                    fn ($query) => $query->whereNull('done_at')->update(['done_at' => now()]),
                    fn ($query) => $query->whereNotNull('done_at')->update(['done_at' => null]),
                );
            }
        });
    }

    /**
     * Labels eines Projekts speichern: bestehende umbenennen, umfärben oder löschen, neue anlegen. Namen sind je Projekt eindeutig.
     *
     * @param  array<int|string, array{name?: string, color?: string, delete?: mixed}>  $rows  Schlüssel = Label-ID
     * @param  list<array{name?: string, color?: string}>  $new
     *
     * @throws DomainException
     */
    public function saveLabels(TaskProject $project, array $rows, array $new): void
    {
        DB::transaction(function () use ($project, $rows, $new): void {
            $names = [];
            $apply = [];
            $color = fn (?string $value): string => array_key_exists((string) $value, TaskLabel::COLORS) ? (string) $value : 'gray';

            foreach ($project->labels()->get() as $label) {
                $row = $rows[$label->id] ?? null;

                if ($row === null || ! empty($row['delete'])) {
                    $apply[] = ['delete', $label];

                    continue;
                }

                $name = mb_substr(trim((string) ($row['name'] ?? '')), 0, 40);

                if ($name === '') {
                    throw new DomainException('Jedes Label braucht einen Namen.');
                }

                $names[] = mb_strtolower($name);
                $apply[] = ['update', $label, ['name' => $name, 'color' => $color($row['color'] ?? null)]];
            }

            foreach ($new as $row) {
                $name = mb_substr(trim((string) ($row['name'] ?? '')), 0, 40);

                if ($name !== '') {
                    $names[] = mb_strtolower($name);
                    $apply[] = ['create', null, ['name' => $name, 'color' => $color($row['color'] ?? null)]];
                }
            }

            if (count($names) !== count(array_unique($names))) {
                throw new DomainException('Label-Namen müssen sich unterscheiden.');
            }

            // Erst löschen und umbenennen, dann anlegen, damit ein freigewordener Name wieder vergeben werden kann.
            foreach ($apply as [$action, $label]) {
                if ($action === 'delete') {
                    $label->delete();
                }
            }

            foreach ($apply as $entry) {
                if ($entry[0] === 'update') {
                    $entry[1]->update(['name' => '~'.$entry[1]->id.'~'.$entry[2]['name']]);
                }
            }

            foreach ($apply as $entry) {
                if ($entry[0] === 'update') {
                    $entry[1]->update($entry[2]);
                } elseif ($entry[0] === 'create') {
                    $project->labels()->create($entry[2]);
                }
            }
        });
    }

    private function createDefaultStatuses(TaskProject $project): void
    {
        $position = 0;

        foreach (TaskProject::DEFAULT_STATUSES as $name => $done) {
            $project->statuses()->create(['name' => $name, 'position' => $position++, 'is_done' => $done]);
        }
    }

    /**
     * @param  list<int>  $managers
     * @param  list<int>  $members
     */
    private function syncMembers(TaskProject $project, array $managers, array $members): void
    {
        $sync = [];

        foreach (array_map('intval', $members) as $id) {
            $sync[$id] = ['role' => 'member'];
        }

        foreach (array_map('intval', $managers) as $id) {
            $sync[$id] = ['role' => 'manager'];
        }

        $project->members()->sync(User::query()->whereIn('id', array_keys($sync))->pluck('id')->mapWithKeys(fn ($id): array => [$id => $sync[$id]])->all());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        return ['name' => trim((string) $data['name']), 'description' => filled($data['description'] ?? null) ? trim((string) $data['description']) : null, 'role_key' => ($data['role_key'] ?? null) ?: null];
    }
}
