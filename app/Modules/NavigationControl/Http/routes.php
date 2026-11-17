<?php

use App\Modules\NavigationControl\Http\Controllers\NavigationController;
use App\Support\PortalAreas;
use Illuminate\Support\Facades\Route;

/* Web-Control für Bereiche und Kopfmenü (Administration): /verwaltung/navigation */
Route::middleware(['web', 'auth', 'can:navigation.manage'])->prefix('verwaltung/navigation')->name('navigation.')->group(function (): void {
    $areas = implode('|', array_map(fn (PortalAreas $area): string => $area->value, \App\Modules\NavigationControl\Support\NavigationControl::togglableAreas()));

    Route::get('/', [NavigationController::class, 'edit'])->name('edit');
    Route::get('/header', [NavigationController::class, 'headers'])->name('headers');
    Route::get('/bereiche/{area}', [NavigationController::class, 'area'])->where('area', $areas)->name('area');
    Route::put('/bereiche/{area}', [NavigationController::class, 'updateArea'])->where('area', $areas)->middleware('throttle:30,10')->name('area.update');
    Route::post('/bereiche/{area}/verschieben', [NavigationController::class, 'move'])->where('area', $areas)->middleware('throttle:60,10')->name('area.move');
    Route::post('/bereiche/{area}/sichtbarkeit', [NavigationController::class, 'toggle'])->where('area', $areas)->middleware('throttle:60,10')->name('area.toggle');
    Route::get('/header/{area}', [NavigationController::class, 'header'])->where('area', $areas)->name('header');
    Route::put('/header/{area}', [NavigationController::class, 'updateHeader'])->where('area', $areas)->middleware('throttle:30,10')->name('header.update');
});
