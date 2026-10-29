<?php

namespace App\Modules\AccessGovernance\Support;

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\Capability;
use App\Modules\AccessGovernance\Enums\RoleKey;

/**
 * Zuordnung Rolle -> Berechtigungen. Eine Berechtigung gilt, wenn der Nutzer mindestens eine zurzeit gültige Rolle hat,
 * die sie enthält. Neue Berechtigungen und Rollen werden hier an einer Stelle gepflegt.
 */
final class Capabilities
{
    /** @var array<string, list<Capability>> */
    private const MAP = [
        'administration' => [Capability::UsersView, Capability::UsersManage, Capability::RolesAssign],
        'administration_staff' => [Capability::UsersView, Capability::UsersManage, Capability::RolesAssign],
        'board_member' => [Capability::UsersView],
    ];

    public static function has(User $user, Capability $capability): bool
    {
        foreach (self::activeRoleKeys($user) as $key) {
            if (in_array($capability, self::MAP[$key] ?? [], true)) {
                return true;
            }
        }

        return false;
    }

    /** Rollen, die der Nutzer jetzt hat (Beginn erreicht, Ende nicht überschritten). @return list<string> */
    public static function activeRoleKeys(User $user): array
    {
        return $user->activeRoleAssignments()
            ->with('role:id,key')
            ->get()
            ->map(fn ($assignment): ?string => $assignment->role?->key)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /** Darf der Nutzer die Rolle vergeben? Administration vergibt nur Administration. */
    public static function canAssign(User $user, RoleKey $role): bool
    {
        if (! self::has($user, Capability::RolesAssign)) {
            return false;
        }

        if ($role === RoleKey::Administration) {
            return in_array(RoleKey::Administration->value, self::activeRoleKeys($user), true);
        }

        return true;
    }
}
