<?php

use App\Modules\Mailings\Http\Controllers\GroupMailingController;
use App\Modules\Mailings\Http\Controllers\MailTemplateController;
use App\Modules\Mailings\Http\Controllers\TeamMailingController;
use Illuminate\Support\Facades\Route;

/*
 * Schreiben per E-Mail:
 *  - an Gruppen: Seite im Bereich Vorstand (mailings.members, darf auch Mitglieder anschreiben) und Seite im Bereich Geschäftsstelle (mailings.send, ohne die Gruppe Mitglieder)
 *  - an Teamende zu Seminaren (Koordination): mailings.team
 *  - Mailvorlagen (Geschäftsstelle, Vorstand): mail_templates.manage
 */
foreach (['board' => ['vorstand/mitteilungen', 'mailings.members'], 'office' => ['geschaeftsstelle/mitteilungen', 'mailings.send']] as $name => [$prefix, $ability]) {
    Route::middleware(['web', 'auth', 'can:'.$ability])->prefix($prefix)->name('mailings.'.$name.'.')->group(function (): void {
        Route::get('/', [GroupMailingController::class, 'index'])->name('index');
        Route::get('/neu', [GroupMailingController::class, 'create'])->name('create');
        Route::post('/vorschau', [GroupMailingController::class, 'preview'])->name('preview');
        Route::post('/test', [GroupMailingController::class, 'test'])->middleware('throttle:10,10')->name('test');
        Route::post('/', [GroupMailingController::class, 'store'])->middleware('throttle:6,10')->name('store');
    });
}

Route::middleware(['web', 'auth', 'can:mailings.team'])->prefix('koordination/mitteilungen')->name('mailings.team.')->group(function (): void {
    Route::get('/', [TeamMailingController::class, 'index'])->name('index');
    Route::get('/neu', [TeamMailingController::class, 'create'])->name('create');
    Route::post('/vorschau', [TeamMailingController::class, 'preview'])->name('preview');
    Route::post('/test', [TeamMailingController::class, 'test'])->middleware('throttle:10,10')->name('test');
    Route::post('/', [TeamMailingController::class, 'store'])->middleware('throttle:10,10')->name('store');
});

Route::middleware(['web', 'auth', 'can:mail_templates.manage'])->prefix('geschaeftsstelle/vorlagen')->name('mailings.templates.')->group(function (): void {
    Route::get('/', [MailTemplateController::class, 'index'])->name('index');
    Route::get('/neu', [MailTemplateController::class, 'create'])->name('create');
    Route::post('/', [MailTemplateController::class, 'store'])->name('store');
    Route::get('/seminar', [MailTemplateController::class, 'team'])->name('team');
    Route::put('/seminar', [MailTemplateController::class, 'updateTeam'])->name('team.update');
    Route::delete('/seminar', [MailTemplateController::class, 'resetTeam'])->name('team.reset');
    Route::get('/{template}/bearbeiten', [MailTemplateController::class, 'edit'])->whereNumber('template')->name('edit');
    Route::put('/{template}', [MailTemplateController::class, 'update'])->whereNumber('template')->name('update');
    Route::delete('/{template}', [MailTemplateController::class, 'destroy'])->whereNumber('template')->name('destroy');
});
