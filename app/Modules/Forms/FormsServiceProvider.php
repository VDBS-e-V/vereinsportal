<?php

namespace App\Modules\Forms;

use Illuminate\Support\ServiceProvider;

class FormsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
        \Illuminate\Support\Facades\Event::listen(\App\Modules\Access\Events\AccountAnonymizing::class, \App\Modules\Forms\Listeners\AnonymizeFormsOnAccountDeletion::class);

        // Auch bei Aufrufen über den Browser (Systemsteuerung, Einrichtung) müssen die Befehle bekannt sein, nicht nur auf der Konsole
        $this->commands([\App\Modules\Forms\Console\PruneForms::class]);
    }
}
