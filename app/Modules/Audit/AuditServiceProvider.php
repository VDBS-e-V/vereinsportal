<?php

namespace App\Modules\Audit;

use App\Modules\Audit\Console\PruneAuditEntries;
use Illuminate\Support\ServiceProvider;

class AuditServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');

        if ($this->app->runningInConsole()) {
            $this->commands([PruneAuditEntries::class]);
        }
    }
}
