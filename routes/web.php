<?php

use App\Support\PortalAreas;
use Illuminate\Support\Facades\Route;

/*
 * Login/Logout liegen im Access-Modul, die Design-Bibliothek unter /design.
 * Weitere Bereiche kommen modulweise dazu (Onboarding, Offboarding, ...).
 *
 * Siehe Konzept: https://claude.ai/artifact/6T9qoinqTT6m6UoQMXptKs
 */
Route::middleware(['web', 'auth'])->get('/', function () {
    return view('portal.home', [
        'areas' => [PortalAreas::Start, PortalAreas::Konto],
    ]);
})->name('home');
