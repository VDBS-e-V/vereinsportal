<?php

use App\Modules\Seminars\Http\Controllers\SeminarController;
use App\Modules\Seminars\Http\Controllers\SeminarPriceController;
use App\Modules\Seminars\Http\Controllers\SeminarQuoteController;
use App\Modules\Seminars\Http\Controllers\SeminarTeamController;
use App\Modules\Seminars\Http\Controllers\SeminarTextController;
use App\Modules\Seminars\Http\Controllers\TeamProfileController;
use App\Modules\Seminars\Http\Controllers\TeamSeminarController;
use Illuminate\Support\Facades\Route;

/*
 * Seminarplanung im Bereich Koordination: /koordination/seminare (Schulseminare mit Terminen, Verlauf und Kostenvoranschlägen) und
 * /koordination/kostenvoranschlaege (Kostenvoranschläge mit Positionen) /koordination/texte (Titel und Standardbeschreibung der Produkte) und /koordination/preise (Preisliste). Berechtigungen seminars.view, seminars.manage und seminars.prices (Support\Capabilities).
 */
Route::middleware(['web', 'auth'])->group(function (): void {
    Route::prefix('koordination/seminare')->name('seminars.')->group(function (): void {
        Route::middleware('can:seminars.manage')->group(function (): void {
            Route::get('/neu', [SeminarController::class, 'create'])->name('create');
            Route::post('/', [SeminarController::class, 'store'])->name('store');
            Route::get('/{seminar}/bearbeiten', [SeminarController::class, 'edit'])->whereNumber('seminar')->name('edit');
            Route::put('/{seminar}', [SeminarController::class, 'update'])->whereNumber('seminar')->name('update');
            Route::delete('/{seminar}', [SeminarController::class, 'destroy'])->whereNumber('seminar')->name('destroy');
            Route::post('/{seminar}/status', [SeminarController::class, 'status'])->whereNumber('seminar')->name('status');
            Route::post('/{seminar}/notiz', [SeminarController::class, 'note'])->whereNumber('seminar')->name('note');
            Route::post('/{seminar}/termine', [SeminarController::class, 'storeSession'])->whereNumber('seminar')->name('sessions.store');
            Route::put('/{seminar}/bedarf', [SeminarTeamController::class, 'need'])->whereNumber('seminar')->name('team.need');
            Route::post('/{seminar}/bewerbungslink', [SeminarTeamController::class, 'createInvite'])->whereNumber('seminar')->middleware('throttle:20,10')->name('team.invite.create');
            Route::delete('/{seminar}/bewerbungslink', [SeminarTeamController::class, 'revokeInvite'])->whereNumber('seminar')->name('team.invite.revoke');
            Route::get('/{seminar}/team/information', [\App\Modules\Seminars\Http\Controllers\TeamInfoController::class, 'show'])->whereNumber('seminar')->name('team.info');
            Route::post('/{seminar}/team/information', [\App\Modules\Seminars\Http\Controllers\TeamInfoController::class, 'send'])->whereNumber('seminar')->middleware('throttle:10,10')->name('team.info.send');
            Route::post('/{seminar}/team/vorschlag', [SeminarTeamController::class, 'suggestion'])->whereNumber('seminar')->name('team.suggestion');
            Route::put('/{seminar}/bewerbungen/{application}/notiz', [SeminarTeamController::class, 'note'])->whereNumber(['seminar', 'application'])->name('team.note');
            Route::post('/{seminar}/bewerbungen/{application}/leitung', [SeminarTeamController::class, 'lead'])->whereNumber(['seminar', 'application'])->name('team.lead');
            Route::post('/{seminar}/bewerbungen/{application}/{decision}', [SeminarTeamController::class, 'decide'])->whereNumber(['seminar', 'application'])->where('decision', 'bestaetigen|ablehnen')->name('team.decide');
            Route::delete('/{seminar}/termine/{session}', [SeminarController::class, 'destroySession'])->whereNumber(['seminar', 'session'])->name('sessions.destroy');
        });

        Route::middleware('can:seminars.manage')->group(function (): void {
            Route::get('/{seminar}/abschluss', [\App\Modules\Seminars\Http\Controllers\SeminarClosureController::class, 'show'])->whereNumber('seminar')->name('closure');
            Route::post('/{seminar}/abschluss', [\App\Modules\Seminars\Http\Controllers\SeminarClosureController::class, 'store'])->whereNumber('seminar')->middleware('throttle:20,10')->name('closure.store');
        });

        Route::post('/{seminar}/abschluss/oeffnen', [\App\Modules\Seminars\Http\Controllers\SeminarClosureController::class, 'reopen'])->whereNumber('seminar')->middleware(['can:seminars.reopen', 'throttle:10,10'])->name('closure.reopen');

        Route::middleware('can:seminars.documents')->group(function (): void {
            Route::post('/{seminar}/dokumente', [\App\Modules\Seminars\Http\Controllers\SeminarDocumentController::class, 'store'])->whereNumber('seminar')->middleware('throttle:30,10')->name('documents.store');
            Route::post('/{seminar}/dokumente/erzeugen', [\App\Modules\Seminars\Http\Controllers\SeminarDocumentController::class, 'generate'])->whereNumber('seminar')->middleware('throttle:20,10')->name('documents.generate');
            Route::post('/{seminar}/dokumente/loeschen', [\App\Modules\Seminars\Http\Controllers\SeminarDocumentController::class, 'destroy'])->whereNumber('seminar')->name('documents.destroy');
            Route::get('/{seminar}/dokumente/{document}', [\App\Modules\Seminars\Http\Controllers\SeminarDocumentController::class, 'download'])->whereNumber(['seminar', 'document'])->name('documents.download');
            Route::get('/{seminar}/mappe', [\App\Modules\Seminars\Http\Controllers\SeminarDocumentController::class, 'folder'])->whereNumber('seminar')->middleware('throttle:10,10')->name('folder');
        });

        Route::middleware('can:seminars.view')->group(function (): void {
            Route::get('/', [SeminarController::class, 'index'])->name('index');
            Route::get('/{seminar}', [SeminarController::class, 'show'])->whereNumber('seminar')->name('show');
        });
    });

    Route::prefix('koordination/seminarstatistik')->name('seminar-stats.')->middleware('can:seminars.view')->group(function (): void {
        Route::get('/', [\App\Modules\Seminars\Http\Controllers\SeminarStatisticsController::class, 'index'])->name('index');
        Route::get('/export', [\App\Modules\Seminars\Http\Controllers\SeminarStatisticsController::class, 'export'])->name('export');
    });

    Route::prefix('geschaeftsstelle/kostenvoranschlaege')->name('seminar-quotes.')->group(function (): void {
        Route::middleware('can:seminars.manage')->group(function (): void {
            Route::get('/neu', [SeminarQuoteController::class, 'create'])->name('create');
            Route::post('/', [SeminarQuoteController::class, 'store'])->name('store');
            Route::get('/{quote}/bearbeiten', [SeminarQuoteController::class, 'edit'])->whereNumber('quote')->name('edit');
            Route::put('/{quote}', [SeminarQuoteController::class, 'update'])->whereNumber('quote')->name('update');
            Route::delete('/{quote}', [SeminarQuoteController::class, 'destroy'])->whereNumber('quote')->name('destroy');
        });

        Route::middleware('can:seminars.view')->group(function (): void {
            Route::get('/', [SeminarQuoteController::class, 'index'])->name('index');
            Route::get('/{quote}', [SeminarQuoteController::class, 'show'])->whereNumber('quote')->name('show');
            Route::get('/{quote}/druck', [SeminarQuoteController::class, 'print'])->whereNumber('quote')->name('print');
        });
    });

    Route::prefix('geschaeftsstelle/texte')->name('seminar-texts.')->group(function (): void {
        Route::put('/', [SeminarTextController::class, 'update'])->middleware('can:seminars.prices')->name('update');
        Route::get('/', [SeminarTextController::class, 'index'])->middleware('can:seminars.view')->name('index');
    });

    Route::prefix('geschaeftsstelle/preise')->name('seminar-prices.')->group(function (): void {
        Route::middleware('can:seminars.prices')->group(function (): void {
            Route::get('/neu', [SeminarPriceController::class, 'create'])->name('create');
            Route::post('/', [SeminarPriceController::class, 'store'])->name('store');
        });

        Route::get('/', [SeminarPriceController::class, 'index'])->middleware('can:seminars.view')->name('index');
    });

    Route::prefix('teamende/profil')->name('team-profile.')->middleware('can:seminars.apply')->group(function (): void {
        Route::get('/', [TeamProfileController::class, 'edit'])->name('edit');
        Route::put('/', [TeamProfileController::class, 'update'])->name('update');
    });

    Route::prefix('teamende/seminare')->name('seminar-applications.')->middleware('can:seminars.apply')->group(function (): void {
        Route::get('/', [TeamSeminarController::class, 'index'])->name('index');
        Route::get('/kalender.ics', [TeamSeminarController::class, 'calendar'])->name('calendar');
        Route::post('/{seminar}/bewerbung', [TeamSeminarController::class, 'apply'])->whereNumber('seminar')->middleware('throttle:20,10')->name('apply');
        Route::delete('/{seminar}/bewerbung', [TeamSeminarController::class, 'withdraw'])->whereNumber('seminar')->name('withdraw');
    });
});

/*
 * Bewerbungslink eines Seminars (öffentlich, ohne Konto): Formular, Bestätigung der E-Mail-Adresse per signiertem Link.
 */
Route::middleware('web')->prefix('team/bewerbung')->name('team-invite.')->group(function (): void {
    Route::get('/gesendet', [\App\Modules\Seminars\Http\Controllers\TeamInviteController::class, 'sent'])->name('sent');
    Route::get('/bestaetigen/{application}', [\App\Modules\Seminars\Http\Controllers\TeamInviteController::class, 'confirm'])->whereNumber('application')->middleware(['signed', 'throttle:30,1'])->name('confirm');
    Route::get('/{token}', [\App\Modules\Seminars\Http\Controllers\TeamInviteController::class, 'show'])->where('token', '[A-Za-z0-9]{20,64}')->middleware('throttle:60,1')->name('show');
    Route::post('/{token}', [\App\Modules\Seminars\Http\Controllers\TeamInviteController::class, 'store'])->where('token', '[A-Za-z0-9]{20,64}')->middleware(['throttle:10,10', 'bot.guard'])->name('store');
});
