<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Der fruehere Cleanup-Job fuer abgelaufene Registrierungsanfragen
 * gehoerte zu app/Modules/Identity (Onboarding) und kommt zurueck,
 * sobald dieses Modul neu aufgebaut ist.
 */
