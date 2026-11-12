<?php

namespace App\Modules\Seminars;

use App\Modules\Seminars\Console\NotifyTeamDeadlines;
use App\Modules\Seminars\Console\NumberExistingSeminars;
use App\Modules\Seminars\Console\PruneApplicants;
use App\Modules\Seminars\Console\PruneDocuments;
use Illuminate\Support\ServiceProvider;

class SeminarsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');

        // Auch bei Aufrufen über den Browser (Systemsteuerung, Einrichtung) müssen die Befehle bekannt sein, nicht nur auf der Konsole
        $this->commands([NotifyTeamDeadlines::class, NumberExistingSeminars::class, PruneApplicants::class, PruneDocuments::class]);
    }
}
