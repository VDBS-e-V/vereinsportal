<?php

use App\Modules\AccessRequests\Http\Controllers\AccessRequestAdminController;
use App\Modules\AccessRequests\Http\Controllers\PublicAccessRequestController;
use Illuminate\Support\Facades\Route;

/*
 * Öffentlich (Bereich Start): Antrag auf Zugang unter /zugang-zum-portal (begrenzt, mit Köder-Feld).
 * Geschäftsstelle: Zugangsanträge unter /verwaltung/zugangsantraege, Berechtigungen access_requests.view und access_requests.manage.
 */
Route::middleware('web')->group(function (): void {
    Route::get('/zugang-zum-portal', [PublicAccessRequestController::class, 'show'])->name('portal.access');
    Route::post('/zugang-zum-portal', [PublicAccessRequestController::class, 'store'])->middleware('throttle:5,60')->name('portal.access.store');
    Route::get('/zugang-zum-portal/danke', [PublicAccessRequestController::class, 'thanks'])->name('portal.access.thanks');
});

Route::middleware(['web', 'auth'])->prefix('verwaltung/zugangsantraege')->name('access-requests.')->group(function (): void {
    Route::middleware('can:access_requests.manage')->group(function (): void {
        Route::post('/{accessRequest}/pruefen', [AccessRequestAdminController::class, 'review'])->whereNumber('accessRequest')->name('review');
        Route::post('/{accessRequest}/annehmen', [AccessRequestAdminController::class, 'approve'])->whereNumber('accessRequest')->name('approve');
        Route::post('/{accessRequest}/ablehnen', [AccessRequestAdminController::class, 'reject'])->whereNumber('accessRequest')->name('reject');
    });

    Route::middleware('can:access_requests.view')->group(function (): void {
        Route::get('/', [AccessRequestAdminController::class, 'index'])->name('index');
        Route::get('/{accessRequest}', [AccessRequestAdminController::class, 'show'])->whereNumber('accessRequest')->name('show');
        Route::get('/{accessRequest}/nachweise/{file}', [AccessRequestAdminController::class, 'download'])->whereNumber(['accessRequest', 'file'])->name('files.download');
    });
});
