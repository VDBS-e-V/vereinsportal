<?php

use App\Modules\Mailings\Http\Controllers\MailTemplateController;
use App\Modules\Mailings\Http\Controllers\MemberMailingController;
use App\Modules\Mailings\Http\Controllers\TeamMailingController;
use Illuminate\Support\Facades\Route;

/*
 * Schreiben per E-Mail: Rundschreiben des Vorstands an Mitglieder (mailings.members), Mitteilungen der Koordination an Teamende (mailings.team)
 * und die Vorlage der Geschäftsstelle dafür (mail_templates.manage).
 */
Route::middleware(['web', 'auth', 'can:mailings.members'])->prefix('vorstand/rundschreiben')->name('mailings.members.')->group(function (): void {
    Route::get('/', [MemberMailingController::class, 'index'])->name('index');
    Route::get('/neu', [MemberMailingController::class, 'create'])->name('create');
    Route::post('/vorschau', [MemberMailingController::class, 'preview'])->name('preview');
    Route::post('/test', [MemberMailingController::class, 'test'])->middleware('throttle:10,10')->name('test');
    Route::post('/', [MemberMailingController::class, 'store'])->middleware('throttle:6,10')->name('store');
});

Route::middleware(['web', 'auth', 'can:mailings.team'])->prefix('koordination/mitteilungen')->name('mailings.team.')->group(function (): void {
    Route::get('/', [TeamMailingController::class, 'index'])->name('index');
    Route::get('/neu', [TeamMailingController::class, 'create'])->name('create');
    Route::post('/vorschau', [TeamMailingController::class, 'preview'])->name('preview');
    Route::post('/test', [TeamMailingController::class, 'test'])->middleware('throttle:10,10')->name('test');
    Route::post('/', [TeamMailingController::class, 'store'])->middleware('throttle:10,10')->name('store');
});

Route::middleware(['web', 'auth', 'can:mail_templates.manage'])->prefix('geschaeftsstelle/vorlagen')->name('mailings.templates.')->group(function (): void {
    Route::get('/', [MailTemplateController::class, 'edit'])->name('edit');
    Route::put('/', [MailTemplateController::class, 'update'])->name('update');
    Route::delete('/', [MailTemplateController::class, 'reset'])->name('reset');
});
