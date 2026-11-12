<?php

use App\Modules\People\Http\Controllers\PersonAdministrationController;
use Illuminate\Support\Facades\Route;

/*
 * Personenverwaltung unter /verwaltung/personen. Berechtigungen people.view und people.manage (Support\Capabilities
 * im Modul AccessGovernance). Ein Konto aus einer Person anlegen erfordert zusätzlich users.manage.
 */
Route::middleware(['web', 'auth'])->prefix('verwaltung/personen')->name('admin.people.')->group(function (): void {
    Route::middleware('can:people.manage')->group(function (): void {
        Route::get('/neu', [PersonAdministrationController::class, 'create'])->name('create');
        Route::post('/', [PersonAdministrationController::class, 'store'])->name('store');
        Route::get('/{person}/bearbeiten', [PersonAdministrationController::class, 'edit'])->whereNumber('person')->name('edit');
        Route::put('/{person}', [PersonAdministrationController::class, 'update'])->whereNumber('person')->name('update');
        Route::delete('/{person}', [PersonAdministrationController::class, 'destroy'])->whereNumber('person')->name('destroy');
        Route::post('/{person}/konto', [PersonAdministrationController::class, 'createAccount'])->whereNumber('person')->middleware('can:users.manage')->name('account');
    });

    Route::middleware('can:people.view')->group(function (): void {
        Route::get('/', [PersonAdministrationController::class, 'index'])->name('index');
        Route::get('/{person}', [PersonAdministrationController::class, 'show'])->whereNumber('person')->name('show');
    });
});
