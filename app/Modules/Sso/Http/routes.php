<?php

use App\Modules\Sso\Http\Controllers\OAuthClientController;
use Illuminate\Support\Facades\Route;

/*
 * Anbindungen im Bereich Admins (oauth.manage, nur Administration): Clients für Nextcloud, Moodle und andere Systeme sowie die Anmeldeschlüssel.
 */
Route::middleware(['web', 'auth', 'can:oauth.manage'])->prefix('admins/anbindungen')->name('admin.oauth.')->group(function (): void {
    Route::get('/', [OAuthClientController::class, 'index'])->name('index');
    Route::get('/neu', [OAuthClientController::class, 'create'])->name('create');
    Route::post('/', [OAuthClientController::class, 'store'])->middleware('throttle:20,10')->name('store');
    Route::post('/schluessel', [OAuthClientController::class, 'createKeys'])->name('keys.create');
    Route::post('/schluessel/erneuern', [OAuthClientController::class, 'renewKeys'])->middleware('throttle:5,10')->name('keys.renew');
    Route::get('/{client}', [OAuthClientController::class, 'show'])->where('client', '[A-Za-z0-9\-]+')->name('show');
    Route::get('/{client}/bearbeiten', [OAuthClientController::class, 'edit'])->where('client', '[A-Za-z0-9\-]+')->name('edit');
    Route::put('/{client}', [OAuthClientController::class, 'update'])->where('client', '[A-Za-z0-9\-]+')->name('update');
    Route::post('/{client}/geheimnis', [OAuthClientController::class, 'renewSecret'])->where('client', '[A-Za-z0-9\-]+')->middleware('throttle:5,10')->name('secret');
    Route::post('/{client}/sperren', [OAuthClientController::class, 'block'])->where('client', '[A-Za-z0-9\-]+')->name('block');
    Route::delete('/{client}', [OAuthClientController::class, 'destroy'])->where('client', '[A-Za-z0-9\-]+')->middleware('throttle:5,10')->name('destroy');
});
