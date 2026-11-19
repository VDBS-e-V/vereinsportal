<?php

use App\Modules\System\Services\SystemChecks;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Herzschlag des Planers: Die Systemsteuerung zeigt daran, ob der Cron-Eintrag läuft.
Schedule::call(fn () => Cache::forever(SystemChecks::HEARTBEAT_KEY, time()))->name('system-heartbeat')->everyMinute();

// Aufbewahrungsfrist: abgelehnte und erledigte Zugangsanträge löschen.
Schedule::command('access-requests:prune')->daily();

// Abgelaufene und widerrufene Tokens der Anbindungen (SSO) entfernen.
Schedule::command('passport:purge')->daily();

// Konten anonymisieren, deren Löschung beantragt wurde und deren Wartefrist abgelaufen ist.
Schedule::command('account:purge')->daily();

/*
 * Der fruehere Cleanup-Job fuer abgelaufene Registrierungsanfragen
 * gehoerte zu app/Modules/Identity (Onboarding) und kommt zurueck,
 * sobald dieses Modul neu aufgebaut ist.
 */
