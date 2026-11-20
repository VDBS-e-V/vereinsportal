<?php

use App\Modules\QuickLinks\Http\Controllers\RoleLinkAdminController;
use Illuminate\Support\Facades\Route;

/* Wichtige Links je Rolle für das Profilmenü pflegen (Administration, Geschäftsstelle): /verwaltung/schnellzugriffe */
Route::middleware(['web', 'auth', 'can:role_links.manage'])->prefix('verwaltung/schnellzugriffe')->name('role-links.')->group(function (): void {
    Route::get('/', [RoleLinkAdminController::class, 'edit'])->name('edit');
    Route::put('/', [RoleLinkAdminController::class, 'update'])->middleware('throttle:30,10')->name('update');
});
