<?php

namespace App\Modules\Events;

use App\Modules\Events\Console\PruneEvents;
use Illuminate\Support\ServiceProvider;

class EventsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');

        if ($this->app->runningInConsole()) {
            $this->commands([PruneEvents::class]);
        }
    }
}
