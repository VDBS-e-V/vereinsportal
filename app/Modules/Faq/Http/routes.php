<?php

use App\Modules\Faq\Http\Controllers\FaqAdminController;
use App\Modules\Faq\Http\Controllers\FaqPageController;
use Illuminate\Support\Facades\Route;

/*
 * Öffentlich (Bereich Start): /faq, die große FAQ-Seite mit Suche.
 * Verwaltung (Geschäftsstelle): Kategorien und Fragen unter /verwaltung/faq mit der Berechtigung faq.manage.
 * Kontaktthemen (Modul ContactForms) können auf eine FAQ-Kategorie verweisen; die FAQ selbst kennt den Kontaktbereich nicht.
 */
Route::middleware('web')->get('/faq', [FaqPageController::class, 'index'])->name('portal.faq');

Route::middleware(['web', 'auth', 'can:faq.manage'])->prefix('verwaltung/faq')->name('faq-admin.')->group(function (): void {
    Route::get('/', [FaqAdminController::class, 'index'])->name('index');
    Route::get('/neu', [FaqAdminController::class, 'create'])->name('create');
    Route::post('/', [FaqAdminController::class, 'store'])->middleware('throttle:60,10')->name('store');
    Route::post('/aktion', [FaqAdminController::class, 'bulk'])->middleware('throttle:60,10')->name('bulk');
    Route::get('/{category}/bearbeiten', [FaqAdminController::class, 'edit'])->whereNumber('category')->name('edit');
    Route::put('/{category}', [FaqAdminController::class, 'update'])->whereNumber('category')->middleware('throttle:60,10')->name('update');
    Route::delete('/{category}', [FaqAdminController::class, 'destroy'])->whereNumber('category')->name('destroy');
});
