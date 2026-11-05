<?php

use App\Modules\Sso\Http\Controllers\ConnectedAppController;
use App\Modules\Sso\Http\Controllers\OAuthClientController;
use App\Modules\Sso\Http\Controllers\OidcController;
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

/*
 * Schnittstellen für angebundene Systeme (öffentlich bzw. mit Bearer-Token): OpenID-Connect-Discovery, Schlüsselliste, Benutzerangaben und Abmeldung.
 */
Route::get('/.well-known/openid-configuration', [OidcController::class, 'discovery'])->middleware('throttle:120,1')->name('oidc.discovery');
Route::get('/oauth/jwks', [OidcController::class, 'jwks'])->middleware('throttle:120,1')->name('oidc.jwks');
Route::match(['get', 'post'], '/oauth/userinfo', [OidcController::class, 'userinfo'])->middleware(['auth:api', 'throttle:120,1'])->name('oidc.userinfo');
Route::get('/oauth/logout', [OidcController::class, 'logout'])->middleware(['web', 'throttle:30,1'])->name('oidc.logout');

// Konto: verbundene Anwendungen ansehen und widerrufen (jedes angemeldete Konto).
Route::middleware(['web', 'auth'])->prefix('konto/anwendungen')->group(function (): void {
    Route::get('/', [ConnectedAppController::class, 'index'])->name('account.apps');
    Route::delete('/{client}', [ConnectedAppController::class, 'destroy'])->where('client', '[A-Za-z0-9\-]+')->name('account.apps.destroy');
});
