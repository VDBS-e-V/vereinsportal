<?php

namespace App\Modules\Contracts;

use Illuminate\Support\ServiceProvider;

class ContractsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');

        // Auch bei Aufrufen über den Browser (Systemsteuerung, Einrichtung) müssen die Befehle bekannt sein, nicht nur auf der Konsole
        $this->commands([\App\Modules\Contracts\Console\PruneContracts::class, \App\Modules\Contracts\Console\RemindContractScans::class]);
    }
}
