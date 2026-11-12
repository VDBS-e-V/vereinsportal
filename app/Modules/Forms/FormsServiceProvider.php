<?php

namespace App\Modules\Forms;

use App\Modules\Access\Events\AccountAnonymizing;
use App\Modules\Forms\Console\PruneForms;
use App\Modules\Forms\Listeners\AnonymizeFormsOnAccountDeletion;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class FormsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
        Event::listen(AccountAnonymizing::class, AnonymizeFormsOnAccountDeletion::class);

        // Auch bei Aufrufen über den Browser (Systemsteuerung, Einrichtung) müssen die Befehle bekannt sein, nicht nur auf der Konsole
        $this->commands([PruneForms::class]);
    }
}
