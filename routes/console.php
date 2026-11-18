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

// Geplante Schreiben an Gruppen starten, wenn ihr Zeitpunkt erreicht ist.
Schedule::command('mailings:dispatch-due')->everyMinute();

// Zustelllisten der Schreiben an Gruppen nach der Aufbewahrungsfrist löschen (Adressen und Fehlergründe).
Schedule::command('mailings:prune')->daily();

// Mitgliedschaften mit abgelaufenem Austrittsdatum beenden, Rolle Vereinsmitglied abgleichen.
Schedule::command('membership:sync')->daily();

// Aufbewahrungsfristen: unbestätigte Online-Anträge, abgelehnte und (falls konfiguriert) beendete Mitgliedschaften löschen.
Schedule::command('membership:prune')->daily();
Schedule::command('access-requests:prune')->daily();

Schedule::command('forms:prune')->daily();

// Abgelaufene und widerrufene Tokens der Anbindungen (SSO) entfernen.
Schedule::command('passport:purge')->daily();

// Teamende mit offener Einarbeitung erinnern (nach 14 Tagen, dann alle 14 Tage, höchstens dreimal).
Schedule::command('onboarding:remind-team')->daily();

// Teamende, die sich noch nicht auf ein ausgeschriebenes Seminar gemeldet haben, drei Tage vor Fristende erinnern.
Schedule::command('seminars:remind-deadlines')->dailyAt('07:10');

// Erinnerungen an bald fällige und überfällige Aufgaben, nur an Personen, die sie eingeschaltet haben.
Schedule::command('tasks:remind')->dailyAt('07:30');

// Angaben externer Gäste von Vorstandssitzungen nach der Aufbewahrungszeit löschen.
Schedule::command('board:prune-guests')->daily();

// Konten anonymisieren, deren Löschung beantragt wurde und deren Wartefrist abgelaufen ist.
Schedule::command('account:purge')->daily();

/*
 * Der fruehere Cleanup-Job fuer abgelaufene Registrierungsanfragen
 * gehoerte zu app/Modules/Identity (Onboarding) und kommt zurueck,
 * sobald dieses Modul neu aufgebaut ist.
 */
