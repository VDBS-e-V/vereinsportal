<?php

namespace App\Modules\AccessGovernance;

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\Capability;
use App\Modules\AccessGovernance\Support\Capabilities;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AccessGovernanceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        foreach (Capability::cases() as $capability) {
            Gate::define($capability->value, fn (User $user): bool => Capabilities::has($user, $capability));
        }

        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
    }
}
