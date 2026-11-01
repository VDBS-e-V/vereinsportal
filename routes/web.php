<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PortalAreaController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SchoolAreaController;
use Illuminate\Support\Facades\Route;

/*
 * Login/Logout liegen im Access-Modul, die Design-Bibliothek unter /design.
 * Weitere Bereiche kommen modulweise dazu (Onboarding, Offboarding, ...).
 *
 * Siehe Konzept: https://claude.ai/artifact/6T9qoinqTT6m6UoQMXptKs
 */
// Startseite: öffentlich, zeigt Gästen den Zugang und Angemeldeten ihre Bereiche.
Route::get('/', [HomeController::class, 'index'])->middleware('web')->name('home');

// Start-Bereich: öffentliche Informationsseiten (Zeile 2 des Kopfbereichs, siehe docs/43_BEREICHE.md).
Route::middleware('web')->group(function (): void {
    Route::view('/ueber-das-portal', 'portal.about')->name('portal.about');
    Route::get('/kontakt', [ContactController::class, 'show'])->name('portal.contact');
    Route::post('/kontakt', [ContactController::class, 'send'])->middleware(['bot.guard', 'throttle:5,10'])->name('portal.contact.send');
});

// Übersichten der Bereiche Mitglieder, Vorstand und Bildungsreferenten
Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/mitglieder', [PortalAreaController::class, 'member'])->middleware('can:member.area')->name('member.home');
    Route::get('/mitglieder/dokumente/{document}', [PortalAreaController::class, 'memberDocument'])->whereNumber('document')->middleware('can:member.area')->name('member.documents.download');
    Route::get('/vorstand', [PortalAreaController::class, 'board'])->middleware('can:memberships.view')->name('board.overview');
    Route::get('/vorstand/berichte', [ReportController::class, 'index'])->middleware('can:reports.view')->name('board.reports');
    Route::get('/vorstand/berichte/export', [ReportController::class, 'export'])->middleware('can:reports.view')->name('board.reports.export');
    Route::get('/bildung', [PortalAreaController::class, 'education'])->middleware('can:education.view')->name('education.overview');
});

// Bereich Schulen (Konten mit der Rolle Schule, nur die eigene Schule)
Route::middleware(['web', 'auth', 'can:school.area'])->prefix('schulen')->name('school.')->group(function (): void {
    Route::get('/', [SchoolAreaController::class, 'home'])->name('home');
    Route::get('/anfrage', [SchoolAreaController::class, 'create'])->name('requests.create');
    Route::post('/anfrage', [SchoolAreaController::class, 'store'])->middleware('throttle:10,10')->name('requests.store');
    Route::get('/veranstaltungen', [SchoolAreaController::class, 'events'])->name('events.index');
    Route::get('/veranstaltungen/{event}', [SchoolAreaController::class, 'event'])->whereNumber('event')->name('events.show');
    Route::get('/veranstaltungen/{event}/vorlage', [SchoolAreaController::class, 'eventTemplate'])->whereNumber('event')->name('events.template');
    Route::post('/veranstaltungen/{event}/liste', [SchoolAreaController::class, 'eventImport'])->whereNumber('event')->middleware('throttle:10,10')->name('events.import');
    Route::delete('/veranstaltungen/{event}/anmeldungen/{registration}', [SchoolAreaController::class, 'eventRegistrationDestroy'])->whereNumber(['event', 'registration'])->name('events.registrations.destroy');
    Route::get('/kostenvoranschlaege/{quote}/druck', [SchoolAreaController::class, 'quote'])->whereNumber('quote')->name('quotes.print');
    Route::post('/seminare/{seminar}/bestaetigen', [SchoolAreaController::class, 'confirm'])->whereNumber('seminar')->middleware('throttle:10,10')->name('seminars.confirm');
    Route::post('/seminare/{seminar}/rueckmeldung', [SchoolAreaController::class, 'feedback'])->whereNumber('seminar')->middleware('throttle:10,10')->name('seminars.feedback');
});
