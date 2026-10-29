<?php

use App\Modules\Membership\Http\Controllers\JoinController;
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
        Route::post('/{membership}/dokumente', [MembershipController::class, 'storeDocument'])->whereNumber('membership')->name('documents.store');
        Route::delete('/{membership}/dokumente/{document}', [MembershipController::class, 'destroyDocument'])->whereNumber(['membership', 'document'])->name('documents.destroy');
        Route::post('/{membership}/registrierungslink', [MembershipController::class, 'sendRegistrationLink'])->whereNumber('membership')->middleware('throttle:10,10')->name('registration-link');
    });

    Route::middleware('can:memberships.view')->group(function (): void {
        Route::get('/', [MembershipController::class, 'index'])->name('index');
        Route::get('/{membership}', [MembershipController::class, 'show'])->whereNumber('membership')->name('show');
        Route::get('/{membership}/dokumente/{document}', [MembershipController::class, 'downloadDocument'])->whereNumber(['membership', 'document'])->name('documents.download');
    });
});

/*
 * Öffentliche Seiten (Bereich Start): Online-Antrag, E-Mail-Bestätigung, Selbstregistrierung per Registrierungslink.
 * Alle Schreibzugriffe sind begrenzt; der Link zur Bestätigung ist signiert.
 */
Route::middleware('web')->group(function (): void {
    Route::get('/mitglied-werden', [JoinController::class, 'apply'])->name('join.apply');
    Route::post('/mitglied-werden', [JoinController::class, 'store'])->middleware('throttle:10,60')->name('join.apply.store');
    Route::get('/mitglied-werden/danke', [JoinController::class, 'applied'])->name('join.applied');
    Route::get('/mitglied-werden/bestaetigen/{user}/{hash}', [JoinController::class, 'verify'])->whereNumber('user')->middleware(['signed', 'throttle:20,10'])->name('join.verify');
    Route::post('/mitglied-werden/bestaetigung-erneut', [JoinController::class, 'resend'])->middleware('throttle:5,10')->name('join.resend');

    Route::get('/registrierung/{token}', [JoinController::class, 'register'])->middleware('throttle:20,10')->name('join.register');
    Route::post('/registrierung/{token}', [JoinController::class, 'registerStore'])->middleware('throttle:10,10')->name('join.register.store');
});
