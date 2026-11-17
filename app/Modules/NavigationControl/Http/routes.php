<?php

use App\Modules\NavigationControl\Http\Controllers\NavigationController;
use Illuminate\Support\Facades\Route;

/* Web-Control für Bereiche und Seiten im Kopfmenü (Administration): /verwaltung/navigation */
Route::middleware(['web', 'auth', 'can:navigation.manage'])->prefix('verwaltung/navigation')->name('navigation.')->group(function (): void {
    Route::get('/', [NavigationController::class, 'edit'])->name('edit');
    Route::put('/', [NavigationController::class, 'update'])->middleware('throttle:30,10')->name('update');
});
