<?php

namespace App\Modules\System;

use App\Modules\System\Console\PortalTickCommand;
use App\Modules\System\Console\PortalUpdateCommand;
use App\Modules\System\Console\VerifyReleaseCommand;
use Illuminate\Support\ServiceProvider;

class SystemServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');

        if ($this->app->runningInConsole()) {
            $this->commands([PortalTickCommand::class, PortalUpdateCommand::class, VerifyReleaseCommand::class]);
        }
    }
}
