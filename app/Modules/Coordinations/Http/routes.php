<?php

use App\Modules\Coordinations\Http\Controllers\CoordinationAdminController;
use Illuminate\Support\Facades\Route;

/* Koordinationen pflegen (Administration, Geschäftsstelle, Vorstand): /verwaltung/koordinationen */
Route::middleware(['web', 'auth', 'can:coordinations.manage'])->prefix('verwaltung/koordinationen')->name('coordinations.')->group(function (): void {
    Route::get('/', [CoordinationAdminController::class, 'index'])->name('index');
    Route::post('/', [CoordinationAdminController::class, 'store'])->middleware('throttle:30,10')->name('store');
    Route::put('/{coordination}', [CoordinationAdminController::class, 'update'])->whereNumber('coordination')->middleware('throttle:30,10')->name('update');
    Route::delete('/{coordination}', [CoordinationAdminController::class, 'destroy'])->whereNumber('coordination')->middleware('throttle:30,10')->name('destroy');
});
