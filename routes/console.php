<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Mitgliedschaften mit abgelaufenem Austrittsdatum beenden, Rolle Vereinsmitglied abgleichen.
Schedule::command('membership:sync')->daily();

// Aufbewahrungsfristen: unbestätigte Online-Anträge, abgelehnte und (falls konfiguriert) beendete Mitgliedschaften löschen.
Schedule::command('membership:prune')->daily();
Schedule::command('access-requests:prune')->daily();

// Konten anonymisieren, deren Löschung beantragt wurde und deren Wartefrist abgelaufen ist.
Schedule::command('account:purge')->daily();

/*
 * Der fruehere Cleanup-Job fuer abgelaufene Registrierungsanfragen
 * gehoerte zu app/Modules/Identity (Onboarding) und kommt zurueck,
 * sobald dieses Modul neu aufgebaut ist.
 */
