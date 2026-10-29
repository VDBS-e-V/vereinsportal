<?php

namespace App\Modules\Access;

use App\Modules\Access\Console\PurgeAccounts;
use Illuminate\Support\ServiceProvider;

class AccessServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');

        if ($this->app->runningInConsole()) {
            $this->commands([PurgeAccounts::class]);
        }
    }
}
