<?php

namespace App\Modules\Tasks;

use Illuminate\Support\ServiceProvider;

class TasksServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');

        // Auch bei Aufrufen über den Browser (Systemsteuerung) müssen die Befehle bekannt sein, nicht nur auf der Konsole
        $this->commands([\App\Modules\Tasks\Console\RemindTasks::class]);
    }
}
