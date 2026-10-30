<?php

use App\Modules\Articles\Http\Controllers\ArticleAdminController;
use App\Modules\Articles\Http\Controllers\PublicArticleController;
use Illuminate\Support\Facades\Route;

/*
 * Öffentlich (Bereich Start): /artikel, /artikel/{slug} und das Bild. Geschäftsstelle: Verwaltung unter /verwaltung/artikel
 * mit der Berechtigung articles.manage (Support\Capabilities im Modul AccessGovernance).
 */
Route::middleware('web')->prefix('artikel')->name('articles.public.')->group(function (): void {
    Route::get('/', [PublicArticleController::class, 'index'])->name('index');
    Route::get('/{slug}', [PublicArticleController::class, 'show'])->where('slug', '[a-z0-9\-]+')->name('show');
    Route::get('/{slug}/bild', [PublicArticleController::class, 'image'])->where('slug', '[a-z0-9\-]+')->name('image');
});

Route::middleware(['web', 'auth', 'can:articles.manage'])->prefix('verwaltung/artikel')->name('articles.admin.')->group(function (): void {
    Route::get('/', [ArticleAdminController::class, 'index'])->name('index');
    Route::get('/neu', [ArticleAdminController::class, 'create'])->name('create');
    Route::post('/', [ArticleAdminController::class, 'store'])->name('store');
    Route::get('/{article}/bearbeiten', [ArticleAdminController::class, 'edit'])->whereNumber('article')->name('edit');
    Route::put('/{article}', [ArticleAdminController::class, 'update'])->whereNumber('article')->name('update');
    Route::delete('/{article}', [ArticleAdminController::class, 'destroy'])->whereNumber('article')->name('destroy');
});
