<?php

use App\Modules\Contacts\Http\Controllers\AccessController;
use App\Modules\Contacts\Http\Controllers\ContactController;
use App\Modules\Contacts\Http\Middleware\EnsureContactAccess;
use Illuminate\Support\Facades\Route;

/*
 * Teamkontakte (Bereich Geschäftsstelle, Recht contacts.manage): Import der Mailingliste, persönliche Zugänge senden und sperren.
 * Der persönliche Zugang selbst (/z/{token}, /zugang) braucht kein Konto und gilt nur für Bewerbungen.
 */
Route::middleware(['web', 'auth', 'can:contacts.manage'])->prefix('geschaeftsstelle/kontakte')->name('contacts.')->group(function (): void {
    Route::get('/', [ContactController::class, 'index'])->name('index');
    Route::get('/neu', [ContactController::class, 'create'])->name('create');
    Route::post('/', [ContactController::class, 'store'])->middleware('throttle:60,10')->name('store');
    Route::get('/import', [ContactController::class, 'importForm'])->name('import');
    Route::get('/import/vorlage', [ContactController::class, 'template'])->name('import.template');
    Route::post('/import/vorschau', [ContactController::class, 'preview'])->middleware('throttle:20,10')->name('import.preview');
    Route::post('/import', [ContactController::class, 'import'])->middleware('throttle:10,10')->name('import.store');
    Route::post('/zugaenge/senden', [ContactController::class, 'sendAll'])->middleware('throttle:5,10')->name('send-all');
    Route::post('/auswahl/senden', [ContactController::class, 'sendSelected'])->middleware('throttle:10,10')->name('send-selected');
    Route::post('/auswahl/loeschen', [ContactController::class, 'deleteSelected'])->middleware('throttle:10,10')->name('delete-selected');
    Route::post('/auswahl/sperren', [ContactController::class, 'revokeSelected'])->name('revoke-selected');
    Route::post('/{contact}/zugang', [ContactController::class, 'send'])->whereNumber('contact')->middleware('throttle:30,10')->name('send');
    Route::post('/{contact}/sperren', [ContactController::class, 'revoke'])->whereNumber('contact')->name('revoke');
    Route::delete('/{contact}', [ContactController::class, 'destroy'])->whereNumber('contact')->name('destroy');
});

Route::middleware('web')->group(function (): void {
    Route::get('/z/{token}', [AccessController::class, 'link'])->where('token', '[A-Za-z0-9]{20,64}')->middleware('throttle:30,1')->name('access.link');
    Route::get('/zugang', [AccessController::class, 'codeForm'])->name('access.code');
    Route::post('/zugang', [AccessController::class, 'codeVerify'])->middleware('throttle:20,1')->name('access.verify');
    Route::post('/zugang/neu', [AccessController::class, 'resend'])->middleware(['throttle:5,10', 'bot.guard'])->name('access.resend');

    Route::middleware(EnsureContactAccess::class)->group(function (): void {
        Route::get('/zugang/start', [AccessController::class, 'home'])->name('access.home');
        Route::post('/zugang/bewerben', [AccessController::class, 'apply'])->name('access.apply');
        Route::post('/zugang/zurueckziehen', [AccessController::class, 'withdraw'])->name('access.withdraw');
        Route::post('/zugang/angaben', [AccessController::class, 'profile'])->name('access.profile');
        Route::post('/zugang/abmelden', [AccessController::class, 'logout'])->name('access.logout');
    });
});
