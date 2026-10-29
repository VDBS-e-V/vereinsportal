<?php

use App\Modules\Seminars\Http\Controllers\SeminarController;
use App\Modules\Seminars\Http\Controllers\SeminarPriceController;
use App\Modules\Seminars\Http\Controllers\SeminarQuoteController;
use Illuminate\Support\Facades\Route;

/*
 * Seminarplanung im Bereich Koordination: /koordination/seminare (Schulseminare mit Terminen, Verlauf und Kostenvoranschlägen) und
 * /koordination/kostenvoranschlaege (Kostenvoranschläge mit Positionen) und /koordination/preise (Preisliste). Berechtigungen seminars.view, seminars.manage und seminars.prices (Support\Capabilities).
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
            Route::delete('/{seminar}/termine/{session}', [SeminarController::class, 'destroySession'])->whereNumber(['seminar', 'session'])->name('sessions.destroy');
        });

        Route::middleware('can:seminars.view')->group(function (): void {
            Route::get('/', [SeminarController::class, 'index'])->name('index');
            Route::get('/{seminar}', [SeminarController::class, 'show'])->whereNumber('seminar')->name('show');
        });
    });

    Route::prefix('koordination/kostenvoranschlaege')->name('seminar-quotes.')->group(function (): void {
        Route::middleware('can:seminars.manage')->group(function (): void {
            Route::get('/neu', [SeminarQuoteController::class, 'create'])->name('create');
            Route::post('/', [SeminarQuoteController::class, 'store'])->name('store');
            Route::delete('/{quote}', [SeminarQuoteController::class, 'destroy'])->whereNumber('quote')->name('destroy');
        });

        Route::middleware('can:seminars.view')->group(function (): void {
            Route::get('/', [SeminarQuoteController::class, 'index'])->name('index');
            Route::get('/{quote}', [SeminarQuoteController::class, 'show'])->whereNumber('quote')->name('show');
            Route::get('/{quote}/druck', [SeminarQuoteController::class, 'print'])->whereNumber('quote')->name('print');
        });
    });

    Route::prefix('koordination/preise')->name('seminar-prices.')->group(function (): void {
        Route::middleware('can:seminars.prices')->group(function (): void {
            Route::get('/neu', [SeminarPriceController::class, 'create'])->name('create');
            Route::post('/', [SeminarPriceController::class, 'store'])->name('store');
        });

        Route::get('/', [SeminarPriceController::class, 'index'])->middleware('can:seminars.view')->name('index');
    });
});
