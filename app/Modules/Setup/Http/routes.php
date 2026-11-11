<?php

use App\Modules\Setup\Http\Controllers\SetupController;
use Illuminate\Support\Facades\Route;

/*
 * Einrichtung im Browser. Ohne Sitzung und ohne Cookies, damit sie auch ohne APP_KEY und ohne Datenbank arbeitet: Jede Seite trägt den Einmal-Token
 * (SETUP_TOKEN) als verstecktes Feld. Es gibt die Adresse nur, solange SETUP_TOKEN gesetzt ist und die Sperrdatei storage/app/setup.lock fehlt.
 */
Route::middleware(\App\Modules\Setup\Http\Middleware\SetupThrottle::class)->prefix('einrichtung')->group(function (): void {
    Route::get('/', [SetupController::class, 'show'])->name('setup.show');
    Route::post('/', [SetupController::class, 'handle'])->name('setup.handle');
});
