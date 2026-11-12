<?php

namespace App\Modules\Contracts;

use App\Modules\Contracts\Console\PruneContracts;
use App\Modules\Contracts\Console\RemindContractScans;
use Illuminate\Support\ServiceProvider;

class ContractsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');

        // Auch bei Aufrufen über den Browser (Systemsteuerung, Einrichtung) müssen die Befehle bekannt sein, nicht nur auf der Konsole
        $this->commands([PruneContracts::class, RemindContractScans::class]);
    }
}
