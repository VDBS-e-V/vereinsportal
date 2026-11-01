<?php

use App\Modules\System\Http\Controllers\SystemController;
use Illuminate\Support\Facades\Route;

/*
 * Systemsteuerung im Bereich Admins: Zustand (nur lesen) und Aufgaben (feste Positivliste). Berechtigung system.manage nur für die Rolle Administration.
 */
Route::middleware(['web', 'auth', 'can:system.manage'])->prefix('verwaltung/system')->name('system.')->group(function (): void {
    Route::get('/', [SystemController::class, 'status'])->name('index');
    Route::get('/aufgaben', [SystemController::class, 'tasks'])->name('tasks');
    Route::post('/aufgaben/{task}', [SystemController::class, 'run'])->where('task', '[a-z0-9\-]+')->name('tasks.run');
});
