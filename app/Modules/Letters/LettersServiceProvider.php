<?php

namespace App\Modules\Letters;

use Illuminate\Support\ServiceProvider;

class LettersServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');

        // Auch bei Aufrufen über den Browser (Systemsteuerung, Update) müssen die Befehle bekannt sein, nicht nur auf der Konsole
        $this->commands([\App\Modules\Letters\Console\LoadLetterDefaults::class]);
    }
}
