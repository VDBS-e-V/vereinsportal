<?php

use App\Modules\Partners\Http\Controllers\PartnerController;
use Illuminate\Support\Facades\Route;

/*
 * Schulen als Partner unter /koordination/schulen (Bereich Koordination). Berechtigungen partners.view und partners.manage
 * (Support\Capabilities im Modul AccessGovernance): Koordination, Geschäftsstelle und Vorstand.
 */
Route::middleware(['web', 'auth'])->prefix('koordination/schulen')->name('partners.')->group(function (): void {
    Route::middleware('can:partners.manage')->group(function (): void {
        Route::get('/neu', [PartnerController::class, 'create'])->name('create');
        Route::post('/', [PartnerController::class, 'store'])->name('store');

        Route::get('/import', [PartnerController::class, 'importForm'])->name('import');
        Route::get('/import/vorlage', [PartnerController::class, 'importTemplate'])->name('import.template');
        Route::post('/import', [PartnerController::class, 'import'])->middleware('throttle:10,10')->name('import.store');

        Route::post('/{partner}/kontakte/{contact}/konto', [PartnerController::class, 'inviteAccount'])->whereNumber(['partner', 'contact'])->middleware('throttle:20,10')->name('contacts.account');
        Route::post('/{partner}/konten/{account}/einladung', [PartnerController::class, 'resendAccountInvitation'])->whereNumber(['partner', 'account'])->middleware('throttle:20,10')->name('accounts.resend');

        Route::get('/{partner}/bearbeiten', [PartnerController::class, 'edit'])->whereNumber('partner')->name('edit');
        Route::put('/{partner}', [PartnerController::class, 'update'])->whereNumber('partner')->name('update');
        Route::delete('/{partner}', [PartnerController::class, 'destroy'])->whereNumber('partner')->name('destroy');
    });

    Route::middleware('can:partners.view')->group(function (): void {
        Route::get('/', [PartnerController::class, 'index'])->name('index');
        Route::get('/export', [PartnerController::class, 'export'])->name('export');
        Route::get('/{partner}', [PartnerController::class, 'show'])->whereNumber('partner')->name('show');
    });
});
