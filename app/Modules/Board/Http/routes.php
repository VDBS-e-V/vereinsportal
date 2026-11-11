<?php

use App\Modules\Board\Http\Controllers\BoardCalendarController;
use Illuminate\Support\Facades\Route;

/* Bereich Vorstand: /vorstand/kalender, gemeinsamer Kalenderlink für die Vorstandssitzungen (Berechtigung board.calendar). */
Route::middleware(['web', 'auth', 'can:board.calendar'])->prefix('vorstand/kalender')->name('board.calendar.')->group(function (): void {
    Route::get('/', [BoardCalendarController::class, 'show'])->name('show');
    Route::put('/', [BoardCalendarController::class, 'update'])->middleware('throttle:20,10')->name('update');
});
