<?php

use Illuminate\Support\Facades\Route;

/*
 * Alle frueheren Routen gehoerten zu app/Modules/Identity (Login,
 * Registrierung, 2FA, Konto, E-Mail-Aenderung, Konto-Loeschung,
 * Portal-Einladung) - dieses Modul wurde im Rahmen des Neuaufbaus
 * entfernt und wird modul- fuer modul neu aufgebaut (Access,
 * Onboarding, Offboarding, ...). Diese Datei bleibt bis dahin leer.
 *
 * Siehe Konzept: https://claude.ai/artifact/6T9qoinqTT6m6UoQMXptKs
 */
Route::get('/', function (): string {
    return 'VDBS Vereinsportal (im Neuaufbau)';
})->name('home');
