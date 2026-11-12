<?php

namespace App\Modules\Board\Services;

use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\Board\Models\BoardMeeting;

/** Auswahlliste der Vorstandsmitglieder (für Anwesenheit und Zuständige): aktive Konten mit Rolle Vorstand, dazu bereits eingetragene Konten. */
final class BoardMembers
{
    /**
     * @param  list<int>  $include  Konten, die trotz fehlender Rolle in der Liste bleiben (zum Beispiel ehemalige Mitglieder in einer alten Sitzung)
     * @return array<int, string> Konto-ID => Name
     */
    public function options(array $include = []): array
    {
        $users = User::query()->with('person')
            ->where(fn ($query) => $query
                ->where(fn ($active) => $active->where('status', UserStatus::Active->value)->whereHas('activeRoleAssignments', fn ($assignments) => $assignments->whereHas('role', fn ($role) => $role->where('key', RoleKey::BoardMember->value))))
                ->when($include !== [], fn ($or) => $or->orWhereIn('id', $include)))
            ->get();

        return $users->mapWithKeys(fn (User $user): array => [$user->id => BoardMeeting::attendeeName($user)])->sort()->all();
    }
}
