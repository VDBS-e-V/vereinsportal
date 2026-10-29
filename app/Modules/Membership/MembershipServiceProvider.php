<?php

namespace App\Modules\Membership;

use App\Modules\Membership\Console\PruneMemberships;
use App\Modules\Membership\Console\SyncMemberships;
use Illuminate\Support\ServiceProvider;

class MembershipServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');

        if ($this->app->runningInConsole()) {
            $this->commands([SyncMemberships::class, PruneMemberships::class]);
        }
    }
}
