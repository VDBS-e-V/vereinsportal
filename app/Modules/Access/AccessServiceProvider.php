<?php

namespace App\Modules\Access;

use App\Modules\Access\Console\GrantAdministration;
use App\Modules\Access\Console\PurgeAccounts;
use Illuminate\Support\ServiceProvider;

class AccessServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');

        // Auch bei Aufrufen über den Browser (Systemsteuerung, Einrichtung) müssen die Befehle bekannt sein, nicht nur auf der Konsole
        $this->commands([PurgeAccounts::class, GrantAdministration::class]);
    }
}
