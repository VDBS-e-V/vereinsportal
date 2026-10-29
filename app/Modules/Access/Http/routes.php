<?php

use App\Modules\Access\Http\Controllers\EmailChangeController;
use App\Modules\Access\Http\Controllers\LoginController;
use App\Modules\Access\Http\Controllers\PasswordController;
use App\Modules\Access\Http\Controllers\PasswordResetController;
use App\Modules\Access\Http\Controllers\ProfileController;
use App\Modules\Access\Http\Controllers\SecurityController;
use App\Modules\Access\Http\Controllers\SessionsController;
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

        // Passwort vergessen
        Route::get('/passwort-vergessen', [PasswordResetController::class, 'request'])->name('password.request');
        Route::post('/passwort-vergessen', [PasswordResetController::class, 'sendLink'])->middleware('throttle:6,1')->name('password.email');
        Route::get('/passwort-zuruecksetzen/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
        Route::post('/passwort-zuruecksetzen', [PasswordResetController::class, 'update'])->middleware('throttle:6,1')->name('password.update');
    });

    Route::post('/logout', [LoginController::class, 'destroy'])
        ->middleware('auth')
        ->name('logout');

    // Konto
    Route::middleware('auth')->prefix('konto')->group(function (): void {
        Route::redirect('/', '/konto/profil');

        Route::get('/profil', [ProfileController::class, 'show'])->name('account.profile');
        Route::put('/profil', [ProfileController::class, 'update'])->name('account.profile.update');

        Route::get('/passwort', [PasswordController::class, 'edit'])->name('account.password');
        Route::put('/passwort', [PasswordController::class, 'update'])->name('account.password.update');

        Route::get('/email', [EmailChangeController::class, 'edit'])->name('account.email');
        Route::post('/email', [EmailChangeController::class, 'store'])->name('account.email.store');
        Route::get('/email/bestaetigen/{publicId}', [EmailChangeController::class, 'confirm'])->middleware('signed')->name('account.email.confirm');

        Route::get('/sitzungen', [SessionsController::class, 'index'])->name('account.sessions');
        Route::delete('/sitzungen', [SessionsController::class, 'destroyOthers'])->name('account.sessions.destroy');
    });

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
