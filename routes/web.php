<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PortalAreaController;
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
    Route::get('/bildung', [PortalAreaController::class, 'education'])->middleware('can:education.view')->name('education.overview');
});
