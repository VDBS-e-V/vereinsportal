<?php

namespace App\Modules\AccessGovernance\Events;

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Models\Role;

/** Einem Konto wurde manuell eine Rolle vergeben. */
final class RoleGranted
{
    public function __construct(public readonly User $user, public readonly Role $role, public readonly ?User $by) {}
}
