<?php

use App\Modules\Access\Http\Controllers\LoginController;
use App\Modules\Access\Http\Controllers\SecurityController;
use App\Modules\Access\Http\Controllers\TwoFactorChallengeController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [LoginController::class, 'create'])->name('login');
        Route::post('/login', [LoginController::class, 'store'])->name('login.store');

        // Zweiter Schritt der Anmeldung (nur nach geprüftem Passwort, siehe TwoFactorChallengeController)
        Route::get('/zwei-faktor', [TwoFactorChallengeController::class, 'create'])->name('two-factor.challenge');
        Route::post('/zwei-faktor', [TwoFactorChallengeController::class, 'store'])->name('two-factor.verify');
        Route::post('/zwei-faktor/email', [TwoFactorChallengeController::class, 'resend'])->name('two-factor.resend');
    });

    Route::post('/logout', [LoginController::class, 'destroy'])
        ->middleware('auth')
        ->name('logout');

    // Konto > Sicherheit
    Route::middleware('auth')->prefix('konto/sicherheit')->name('account.security.')->group(function (): void {
        Route::get('/', [SecurityController::class, 'index'])->name('index');

        Route::get('/app', [SecurityController::class, 'totpCreate'])->name('totp');
        Route::post('/app', [SecurityController::class, 'totpStore'])->name('totp.store');

        Route::get('/email', [SecurityController::class, 'emailCreate'])->name('email');
        Route::post('/email', [SecurityController::class, 'emailStore'])->name('email.store');
        Route::post('/email/senden', [SecurityController::class, 'emailResend'])->name('email.resend');

        Route::get('/wiederherstellungscodes', [SecurityController::class, 'recoveryShow'])->name('recovery');
        Route::post('/wiederherstellungscodes', [SecurityController::class, 'recoveryStore'])->name('recovery.store');

        Route::delete('/{type}', [SecurityController::class, 'destroy'])->whereIn('type', ['totp', 'email'])->name('destroy');
    });
});
