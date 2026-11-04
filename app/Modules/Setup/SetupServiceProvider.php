<?php

namespace App\Modules\Setup;

use Illuminate\Support\ServiceProvider;

/** Modul: Setup. Einrichtung im Browser für Webhosting ohne Konsole (/einrichtung). */
class SetupServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
    }
}
