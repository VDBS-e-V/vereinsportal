<?php

namespace App\Modules\Administration\Support;

use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;

final class AdministrationAccess
{
    /**
     * Rollen, die den Verwaltungsbereich betreten dürfen.
     *
     * Fachliche Verwaltungsmitarbeitende und technische Administration
     * erhalten Lesezugriff. Schreibende Verwaltungsaktionen bleiben
     * ausschließlich der technischen Administration vorbehalten.
     */
    private const ACCESS_ROLE_KEYS = [
        RoleKey::AdministrationStaff->value,
        RoleKey::Administration->value,
    ];

    private const MANAGEMENT_ROLE_KEYS = [
        RoleKey::Administration->value,
    ];

    public function allows(?User $user): bool
    {
        return $this->hasActiveRole(
            $user,
            self::ACCESS_ROLE_KEYS,
        );
    }

    public function canManage(?User $user): bool
    {
        return $this->hasActiveRole(
            $user,
            self::MANAGEMENT_ROLE_KEYS,
        );
    }

    /**
     * @param list<string> $roleKeys
     */
    private function hasActiveRole(
        ?User $user,
        array $roleKeys,
    ): bool {
        if (
            $user === null
            || $user->status !== UserStatus::Active
            || $user->email_verified_at === null
        ) {
            return false;
        }

        $now = now();

        return $user
            ->roleAssignments()
            ->where(
                'starts_at',
                '<=',
                $now,
            )
            ->where(function ($query) use ($now): void {
                $query
                    ->whereNull('ends_at')
                    ->orWhere(
                        'ends_at',
                        '>',
                        $now,
                    );
            })
            ->whereHas(
                'role',
                fn ($query) => $query->whereIn(
                    'key',
                    $roleKeys,
                ),
            )
            ->exists();
    }
}
