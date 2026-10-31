<?php

namespace App\Modules\Seminars;

use App\Modules\Seminars\Console\NotifyTeamDeadlines;
use Illuminate\Support\ServiceProvider;

class SeminarsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');

        if ($this->app->runningInConsole()) {
            $this->commands([NotifyTeamDeadlines::class]);
        }
    }
}
