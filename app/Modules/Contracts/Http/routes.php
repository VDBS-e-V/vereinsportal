<?php

use App\Modules\Contracts\Http\Controllers\ContractAdminController;
use App\Modules\Contracts\Http\Controllers\ContractController;
use App\Modules\Contracts\Http\Controllers\ContractorProfileController;
use App\Modules\Contracts\Http\Controllers\SchoolContractController;
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

    // Vertrag mit der Schule (Geschäftsstelle)
    Route::middleware('can:seminars.review')->group(function (): void {
        Route::prefix('geschaeftsstelle/seminare/{seminar}/schulvertrag')->name('school-contracts.')->whereNumber('seminar')->group(function (): void {
            Route::get('/', [SchoolContractController::class, 'show'])->name('show');
            Route::post('/', [SchoolContractController::class, 'store'])->middleware('throttle:20,10')->name('store');
            Route::get('/{contract}/drucken', [SchoolContractController::class, 'print'])->whereNumber('contract')->name('print');
            Route::post('/{contract}/unterschrift', [SchoolContractController::class, 'sign'])->whereNumber('contract')->middleware('throttle:20,10')->name('sign');
            Route::post('/{contract}/stornieren', [SchoolContractController::class, 'cancel'])->whereNumber('contract')->middleware('throttle:20,10')->name('cancel');
        });
        Route::get('geschaeftsstelle/schulvertrag-vorlage', [SchoolContractController::class, 'template'])->name('school-contracts.template');
        Route::put('geschaeftsstelle/schulvertrag-vorlage', [SchoolContractController::class, 'updateTemplate'])->middleware('throttle:20,10')->name('school-contracts.template.update');
    });

    Route::prefix('teamende/vertragsdaten')->name('contractor.')->middleware('can:seminars.apply')->group(function (): void {
        Route::get('/', [ContractorProfileController::class, 'edit'])->name('edit');
        Route::put('/', [ContractorProfileController::class, 'update'])->name('update');
        Route::delete('/', [ContractorProfileController::class, 'destroy'])->name('destroy');
    });
});
