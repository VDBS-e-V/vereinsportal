<?php

use App\Modules\Events\Http\Controllers\EventAdminController;
use App\Modules\Events\Http\Controllers\PublicEventController;
use Illuminate\Support\Facades\Route;

/*
 * Öffentlich (Bereich Start): /veranstaltungen (Liste, Anmeldung, Bestätigungslink) und /veranstaltungen/teilnahmebestaetigung.
 * Koordination: /koordination/veranstaltungen mit den Berechtigungen events.view und events.manage (Support\Capabilities).
 */
Route::middleware('web')->prefix('veranstaltungen')->name('events.public.')->group(function (): void {
    Route::get('/', [PublicEventController::class, 'index'])->name('index');
    Route::get('/teilnahmebestaetigung', [PublicEventController::class, 'certificateForm'])->name('certificate');
    Route::post('/teilnahmebestaetigung', [PublicEventController::class, 'certificate'])->middleware(['bot.guard', 'throttle:10,10'])->name('certificate.show');
    Route::get('/anmeldung/{registration}/bestaetigen', [PublicEventController::class, 'confirm'])->whereNumber('registration')->middleware(['signed', 'throttle:20,10'])->name('confirm');
    Route::get('/{slug}', [PublicEventController::class, 'show'])->where('slug', '[a-z0-9\-]+')->name('show');
    Route::post('/{slug}/anmeldung', [PublicEventController::class, 'register'])->where('slug', '[a-z0-9\-]+')->middleware(['bot.guard', 'throttle:10,10'])->name('register');
});

Route::middleware(['web', 'auth'])->prefix('koordination/veranstaltungen')->name('events.admin.')->group(function (): void {
    Route::middleware('can:events.manage')->group(function (): void {
        Route::get('/neu', [EventAdminController::class, 'create'])->name('create');
        Route::post('/', [EventAdminController::class, 'store'])->name('store');
        Route::get('/{event}/bearbeiten', [EventAdminController::class, 'edit'])->whereNumber('event')->name('edit');
        Route::put('/{event}', [EventAdminController::class, 'update'])->whereNumber('event')->name('update');
        Route::delete('/{event}', [EventAdminController::class, 'destroy'])->whereNumber('event')->name('destroy');
        Route::post('/{event}/termine', [EventAdminController::class, 'storeSession'])->whereNumber('event')->name('sessions.store');
        Route::delete('/{event}/termine/{session}', [EventAdminController::class, 'destroySession'])->whereNumber(['event', 'session'])->name('sessions.destroy');
        Route::get('/{event}/import', [EventAdminController::class, 'importForm'])->whereNumber('event')->name('import');
        Route::get('/{event}/import/vorlage', [EventAdminController::class, 'importTemplate'])->whereNumber('event')->name('import.template');
        Route::post('/{event}/import', [EventAdminController::class, 'import'])->whereNumber('event')->middleware('throttle:10,10')->name('import.store');
        Route::delete('/{event}/anmeldungen/{registration}', [EventAdminController::class, 'destroyRegistration'])->whereNumber(['event', 'registration'])->name('registrations.destroy');
        Route::post('/{event}/anwesenheit/{session}', [EventAdminController::class, 'mark'])->whereNumber(['event', 'session'])->name('attendance.mark');
        Route::post('/{event}/anwesenheit/{session}/ticket', [EventAdminController::class, 'markTicket'])->whereNumber(['event', 'session'])->name('attendance.ticket');
    });

    Route::middleware('can:events.view')->group(function (): void {
        Route::get('/', [EventAdminController::class, 'index'])->name('index');
        Route::get('/{event}', [EventAdminController::class, 'show'])->whereNumber('event')->name('show');
        Route::get('/{event}/export', [EventAdminController::class, 'export'])->whereNumber('event')->name('export');
        Route::get('/{event}/anwesenheit/{session}', [EventAdminController::class, 'attendance'])->whereNumber(['event', 'session'])->name('attendance');
        Route::get('/{event}/anmeldungen/{registration}/bestaetigung', [EventAdminController::class, 'certificate'])->whereNumber(['event', 'registration'])->name('registrations.certificate');
    });
});
