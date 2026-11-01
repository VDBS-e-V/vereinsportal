<?php

namespace App\Modules\DesignLibrary;

use App\Modules\DesignLibrary\Console\DesignCheckCommand;
use App\Modules\DesignLibrary\Console\DesignComponentCommand;
use App\Modules\DesignLibrary\Console\DesignPackageCommand;
use Illuminate\Support\ServiceProvider;

class DesignLibraryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');

        if ($this->app->runningInConsole()) {
            $this->commands([DesignPackageCommand::class, DesignComponentCommand::class, DesignCheckCommand::class]);
        }
    }
}
