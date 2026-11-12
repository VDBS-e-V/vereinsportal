<?php

namespace App\Modules\DesignLibrary;

use App\Modules\DesignLibrary\Console\DesignAdoptCommand;
use App\Modules\DesignLibrary\Console\DesignCheckCommand;
use App\Modules\DesignLibrary\Console\DesignComponentCommand;
use App\Modules\DesignLibrary\Console\DesignPackageCommand;
use App\Modules\DesignLibrary\Console\DesignStatusCommand;
use Illuminate\Support\ServiceProvider;

class DesignLibraryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');

        // Auch bei Aufrufen über den Browser (Systemsteuerung, Einrichtung) müssen die Befehle bekannt sein, nicht nur auf der Konsole
        $this->commands([DesignPackageCommand::class, DesignComponentCommand::class, DesignCheckCommand::class, DesignAdoptCommand::class, DesignStatusCommand::class]);
    }
}
