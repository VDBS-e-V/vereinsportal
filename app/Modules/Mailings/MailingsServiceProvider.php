<?php

namespace App\Modules\Mailings;

use App\Modules\Mailings\Console\DispatchDueMailings;
use App\Modules\Mailings\Console\LoadMailDefaults;
use App\Modules\Mailings\Console\PruneMailingDeliveries;
use Illuminate\Support\ServiceProvider;

class MailingsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');

        if ($this->app->runningInConsole()) {
            $this->commands([PruneMailingDeliveries::class, DispatchDueMailings::class, LoadMailDefaults::class]);
        }
    }
}
