<?php

namespace App\Modules\Seminars\Support;

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use Illuminate\Support\Collection;

/** Die Konten, die für die Koordination Nachrichten bekommen (Rolle Koordination, zurzeit gültig). */
final class CoordinationRecipients
{
    /** @return Collection<int, User> */
    public static function users(): Collection
    {
        return User::query()->whereIn('id', RoleAssignment::query()
            ->where('starts_at', '<=', now())->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->whereHas('role', fn ($query) => $query->where('key', RoleKey::Coordination->value))->select('user_id'))->get();
    }
}
