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

// Koordination informieren, wenn der Stichtag für Interessenbekundungen auf ein Seminar vorbei ist.
Schedule::command('seminars:team-deadlines')->daily();

// Teamende mit offener Einarbeitung erinnern (nach 14 Tagen, dann alle 14 Tage, höchstens dreimal).
Schedule::command('onboarding:remind-team')->daily();

// Unbestätigte Anmeldungen zu Großveranstaltungen und Anmeldungen nach der Aufbewahrungszeit löschen.
Schedule::command('events:prune')->daily();

// Konten anonymisieren, deren Löschung beantragt wurde und deren Wartefrist abgelaufen ist.
Schedule::command('account:purge')->daily();

/*
 * Der fruehere Cleanup-Job fuer abgelaufene Registrierungsanfragen
 * gehoerte zu app/Modules/Identity (Onboarding) und kommt zurueck,
 * sobald dieses Modul neu aufgebaut ist.
 */
