<?php

namespace App\Modules\QuickLinks\Support;

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Support\Capabilities;
use App\Modules\QuickLinks\Models\RoleLink;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Throwable;

/** Die wichtigsten Links aller Rollen einer Person für das Profilmenü. */
final class QuickLinks
{
    public const LIMIT = 4;

    /**
     * Links aller aktiven Rollen, nach Wichtigkeit sortiert, ohne doppelte Adressen, höchstens LIMIT.
     *
     * @return Collection<int, RoleLink>
     */
    public static function forUser(User $user, int $limit = self::LIMIT): Collection
    {
        try {
            $roles = Capabilities::activeRoleKeys($user);

            if ($roles === [] || ! Schema::hasTable('role_links')) {
                return collect();
            }

            return RoleLink::query()->whereIn('role_key', $roles)->orderBy('priority')->orderBy('id')->get()->unique('url')->take($limit)->values();
        } catch (Throwable) {
            return collect(); // Das Menü darf nie an den Links scheitern.
        }
    }
}
