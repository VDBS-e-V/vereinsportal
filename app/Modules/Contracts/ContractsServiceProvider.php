<?php

namespace App\Modules\Contracts;

use Illuminate\Support\ServiceProvider;

class ContractsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');

        if ($this->app->runningInConsole()) {
            $this->commands([\App\Modules\Contracts\Console\PruneContracts::class]);
        }
    }
}
