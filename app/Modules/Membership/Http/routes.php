<?php

use App\Modules\Membership\Http\Controllers\MembershipController;
use Illuminate\Support\Facades\Route;

/*
 * Mitgliedschaften unter /vorstand/mitgliedschaften (Bereich Vorstand). Berechtigungen memberships.view und
 * memberships.manage (Support\Capabilities im Modul AccessGovernance), derzeit nur für den Vorstand.
 */
Route::middleware(['web', 'auth'])->prefix('vorstand/mitgliedschaften')->name('membership.')->group(function (): void {
    Route::middleware('can:memberships.manage')->group(function (): void {
        Route::get('/neu', [MembershipController::class, 'create'])->name('create');
        Route::post('/', [MembershipController::class, 'store'])->name('store');

        Route::post('/{membership}/aufnehmen', [MembershipController::class, 'admit'])->whereNumber('membership')->name('admit');
        Route::post('/{membership}/ablehnen', [MembershipController::class, 'reject'])->whereNumber('membership')->name('reject');
        Route::post('/{membership}/austritt', [MembershipController::class, 'terminate'])->whereNumber('membership')->name('terminate');
        Route::post('/{membership}/austritt-zuruecknehmen', [MembershipController::class, 'withdraw'])->whereNumber('membership')->name('withdraw');
        Route::post('/{membership}/notiz', [MembershipController::class, 'note'])->whereNumber('membership')->name('note');
    });

    Route::middleware('can:memberships.view')->group(function (): void {
        Route::get('/', [MembershipController::class, 'index'])->name('index');
        Route::get('/{membership}', [MembershipController::class, 'show'])->whereNumber('membership')->name('show');
    });
});
