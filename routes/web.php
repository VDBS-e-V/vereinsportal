<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotFoundController;
use App\Http\Controllers\PortalAreaController;
use Illuminate\Support\Facades\Route;

/*
 * Login/Logout liegen im Access-Modul, die Design-Bibliothek unter /design.
 * Diese Datei enthält die Startseite, die öffentlichen Informationsseiten und die Übersichten der Bereiche.
 */
// Startseite: öffentlich, zeigt Gästen den Zugang und Angemeldeten ihre Bereiche.
Route::get('/', [HomeController::class, 'index'])->middleware('web')->name('home');

// Start-Bereich: öffentliche Informationsseiten (Zeile 2 des Kopfbereichs, siehe docs/43_BEREICHE.md).
Route::middleware('web')->group(function (): void {
    Route::view('/ueber-das-portal', 'portal.about')->name('portal.about');
    Route::view('/design-pakete', 'portal.design-packages')->name('portal.design-packages');
});

// Übersichten der Bereiche (jede zeigt die Seiten, die die Person im Bereich sehen darf)
Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/mitglieder', [PortalAreaController::class, 'area'])->defaults('area', 'mitglieder')->middleware('can:member.area')->name('member.home');
    Route::get('/vorstand', [PortalAreaController::class, 'area'])->defaults('area', 'vorstand')->name('board.overview');
    Route::get('/teamende', [PortalAreaController::class, 'area'])->defaults('area', 'teamende')->name('team.home');
    Route::get('/mitarbeitende', [PortalAreaController::class, 'area'])->defaults('area', 'mitarbeitende')->middleware('can:employee.area')->name('employee.home');
    Route::get('/schulen', [PortalAreaController::class, 'area'])->defaults('area', 'schulen')->middleware('can:school.area')->name('school.home');
    Route::get('/koordination', [PortalAreaController::class, 'area'])->defaults('area', 'koordination')->name('coordination.home');
    Route::get('/geschaeftsstelle', [PortalAreaController::class, 'area'])->defaults('area', 'geschaeftsstelle')->name('office.home');
    Route::get('/admins', [PortalAreaController::class, 'area'])->defaults('area', 'admins')->name('admins.home');
    Route::get('/bildung', [PortalAreaController::class, 'area'])->defaults('area', 'bildungsreferenten')->middleware('can:education.view')->name('education.overview');
});

// Unbekannte Adressen laufen durch die Web-Middleware, damit die 404-Seite die Anmeldung kennt (Bereiche statt Login).
// Alle Methoden, damit eine unbekannte Adresse nicht plötzlich 405 statt 404 liefert.
Route::match(['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '{fallbackPlaceholder}', [NotFoundController::class, '__invoke'])->where('fallbackPlaceholder', '.*')->fallback();
