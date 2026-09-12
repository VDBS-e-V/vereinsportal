<?php

namespace App\Modules\Identity\Support;

use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Models\User;

final class AccountAccess
{
    public function hasActiveRole(?User $user, RoleKey $roleKey): bool
    {
        if ($user === null) {
            return false;
        }

        $now = now();

        return $user
            ->roleAssignments()
            ->where('starts_at', '<=', $now)
            ->where(function ($query) use ($now): void {
                $query
                    ->whereNull('ends_at')
                    ->orWhere('ends_at', '>', $now);
            })
            ->whereHas(
                'role',
                fn ($query) => $query->where('key', $roleKey->value),
            )
            ->exists();
    }
}
