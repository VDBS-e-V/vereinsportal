<?php

use App\Modules\Pages\Enums\PageKey;
use App\Modules\Pages\Http\Controllers\PageAdminController;
use App\Modules\Pages\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

/*
 * Öffentlich (Bereich Start): /impressum, /datenschutz, /barrierefreiheit, /nutzungsbedingungen.
 * Geschäftsstelle: Pflege unter /verwaltung/seiten mit der Berechtigung pages.manage.
 */
Route::middleware('web')->get('/{page}', [PageController::class, 'show'])->where('page', PageKey::pattern())->name('pages.show');

Route::middleware(['web', 'auth', 'can:pages.manage'])->prefix('verwaltung/seiten')->name('pages.admin.')->group(function (): void {
    Route::get('/', [PageAdminController::class, 'index'])->name('index');
    Route::get('/{page}', [PageAdminController::class, 'edit'])->where('page', PageKey::pattern())->name('edit');
    Route::put('/{page}', [PageAdminController::class, 'update'])->where('page', PageKey::pattern())->name('update');
});
