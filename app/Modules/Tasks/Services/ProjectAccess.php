<?php

namespace App\Modules\Tasks\Services;

use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Support\Capabilities;
use App\Modules\Board\Models\BoardMeeting;
use App\Modules\Tasks\Models\TaskProject;
use Illuminate\Database\Eloquent\Builder;

/**
 * Wer ein Projekt sieht und verwaltet: Mitglieder (ausdrücklich eingetragen oder über die Rolle des Projekts) sehen und bearbeiten Aufgaben.
 * Verwalten (Spalten, Mitglieder, Einstellungen) dürfen Verwaltende des Projekts, Inhaber der Projektrolle und tasks.admin.
 * Gesehen wird nie mehr als das, wofür man Mitglied ist; tasks.admin sieht alle Projekte.
 */
final class ProjectAccess
{
    public function isAdmin(User $user): bool
    {
        return $user->can('tasks.admin');
    }

    public function canView(User $user, TaskProject $project): bool
    {
        return $user->can('tasks.use') && ($this->isAdmin($user) || $this->holdsRole($user, $project) || $this->membership($user, $project) !== null);
    }

    public function canManage(User $user, TaskProject $project): bool
    {
        return $user->can('tasks.use') && ($this->isAdmin($user) || $this->holdsRole($user, $project) || $this->membership($user, $project) === 'manager');
    }

    /** @return Builder<TaskProject> */
    public function visible(User $user): Builder
    {
        $query = TaskProject::query();

        if (! $user->can('tasks.use')) {
            return $query->whereRaw('1 = 0');
        }

        if ($this->isAdmin($user)) {
            return $query;
        }

        $roles = Capabilities::activeRoleKeys($user);

        return $query->where(fn (Builder $inner) => $inner
            ->whereHas('members', fn ($members) => $members->where('users.id', $user->id))
            ->orWhereIn('role_key', $roles ?: ['-']));
    }

    /**
     * Konten, denen in diesem Projekt Aufgaben zugewiesen werden können: ausdrücklich eingetragene Mitglieder und aktive Inhaber der Projektrolle.
     *
     * @param  list<int>  $include  Konten, die trotzdem erscheinen (bereits Zuständige einer Aufgabe)
     * @return array<int, string> Konto-ID => Name
     */
    public function assignable(TaskProject $project, array $include = []): array
    {
        $users = User::query()->with('person')->where('status', UserStatus::Active->value)
            ->where(fn (Builder $inner) => $inner
                ->whereHas('taskProjectMemberships', fn ($members) => $members->where('task_projects.id', $project->id))
                ->when($project->role_key, fn ($or) => $or->orWhereHas('activeRoleAssignments', fn ($assignments) => $assignments->whereHas('role', fn ($role) => $role->where('key', $project->role_key)))))
            ->when($include !== [], fn ($query) => $query->orWhereIn('id', $include))
            ->get();

        return $users->mapWithKeys(fn (User $user): array => [$user->id => BoardMeeting::attendeeName($user)])->sort()->all();
    }

    /** @return 'manager'|'member'|null */
    private function membership(User $user, TaskProject $project): ?string
    {
        $role = $project->members()->where('users.id', $user->id)->first()?->pivot->role;

        return $role === null ? null : (string) $role;
    }

    private function holdsRole(User $user, TaskProject $project): bool
    {
        return $project->role_key !== null && in_array($project->role_key, Capabilities::activeRoleKeys($user), true);
    }
}
