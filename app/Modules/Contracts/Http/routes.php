<?php

use App\Modules\Contracts\Http\Controllers\ContractAdminController;
use App\Modules\Contracts\Http\Controllers\ContractController;
use App\Modules\Contracts\Http\Controllers\ContractorProfileController;
use Illuminate\Support\Facades\Route;

/*
 * Honorarvertrag: Verträge je Seminar (contracts.manage), Vertragstext und Vereinsdaten (contracts.manage, Textänderung nur contracts.template),
 * eigene Vertragsdaten der Teamenden.
 */
Route::middleware(['web', 'auth'])->group(function (): void {
    Route::prefix('seminare/{seminar}/vertraege')->name('contracts.')->middleware('can:contracts.manage')->whereNumber('seminar')->group(function (): void {
        Route::post('/', [ContractController::class, 'store'])->middleware('throttle:20,10')->name('store');
        Route::get('/drucken', [ContractController::class, 'printAll'])->name('print');
        Route::get('/{contract}', [ContractController::class, 'show'])->whereNumber('contract')->name('show');
        Route::get('/{contract}/bearbeiten', [ContractController::class, 'edit'])->whereNumber('contract')->name('edit');
        Route::put('/{contract}', [ContractController::class, 'update'])->whereNumber('contract')->name('update');
        Route::post('/{contract}/scan', [ContractController::class, 'scan'])->whereNumber('contract')->middleware('throttle:30,10')->name('scan');
        Route::post('/{contract}/stornieren', [ContractController::class, 'cancel'])->whereNumber('contract')->name('cancel');
    });

    Route::prefix('koordination/vertraege')->name('contracts.admin.')->middleware('can:contracts.manage')->group(function (): void {
        Route::get('/', [ContractAdminController::class, 'index'])->name('index');
        Route::put('/vereinsdaten', [ContractAdminController::class, 'updateSettings'])->name('settings');
        Route::post('/vertragstext', [ContractAdminController::class, 'storeVersion'])->middleware(['can:contracts.template', 'throttle:20,10'])->name('version');
    });

    Route::prefix('teamende/vertragsdaten')->name('contractor.')->middleware('can:seminars.apply')->group(function (): void {
        Route::get('/', [ContractorProfileController::class, 'edit'])->name('edit');
        Route::put('/', [ContractorProfileController::class, 'update'])->name('update');
        Route::delete('/', [ContractorProfileController::class, 'destroy'])->name('destroy');
    });
});
