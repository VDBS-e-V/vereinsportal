<?php

use App\Modules\Mailings\Http\Controllers\GroupMailingController;
use App\Modules\Mailings\Http\Controllers\MailingProgressController;
use App\Modules\Mailings\Http\Controllers\MailTemplateController;
use App\Modules\Mailings\Http\Controllers\SuppressionController;
use App\Modules\Mailings\Http\Controllers\UnsubscribeController;
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
        Route::get('/gesperrt', [SuppressionController::class, 'index'])->name('suppressions');
        Route::post('/gesperrt', [SuppressionController::class, 'store'])->middleware('throttle:20,10')->name('suppressions.store');
        Route::post('/gesperrt/{suppression}/freigeben', [SuppressionController::class, 'release'])->whereNumber('suppression')->name('suppressions.release');
        Route::get('/{mailing}', [MailingProgressController::class, 'show'])->whereNumber('mailing')->name('show');
        Route::post('/{mailing}/abbrechen', [MailingProgressController::class, 'cancel'])->whereNumber('mailing')->name('cancel');
        Route::post('/{mailing}/wiederholen', [MailingProgressController::class, 'retry'])->whereNumber('mailing')->name('retry');
    });
}

Route::middleware(['web', 'auth', 'can:mailings.team'])->prefix('koordination/mitteilungen')->name('mailings.team.')->group(function (): void {
    Route::get('/', [TeamMailingController::class, 'index'])->name('index');
    Route::get('/neu', [TeamMailingController::class, 'create'])->name('create');
    Route::post('/vorschau', [TeamMailingController::class, 'preview'])->name('preview');
    Route::post('/test', [TeamMailingController::class, 'test'])->middleware('throttle:10,10')->name('test');
    Route::post('/', [TeamMailingController::class, 'store'])->middleware('throttle:10,10')->name('store');
    Route::get('/{mailing}', [MailingProgressController::class, 'show'])->whereNumber('mailing')->name('show');
    Route::post('/{mailing}/abbrechen', [MailingProgressController::class, 'cancel'])->whereNumber('mailing')->name('cancel');
    Route::post('/{mailing}/wiederholen', [MailingProgressController::class, 'retry'])->whereNumber('mailing')->name('retry');
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

/*
 * Abmelden ohne Anmeldung (Link in den Mails). Der POST nimmt auch den One-Click-Aufruf der Postfachanbieter entgegen (RFC 8058): Er kommt ohne CSRF-Token
 * und ohne Sitzung, deshalb ist die Prüfung hier ausgenommen. Geschützt ist die Adresse durch den verschlüsselten Token und eine Drosselung.
 */
Route::middleware(['web', 'throttle:60,1'])->prefix('abmelden')->name('mailings.unsubscribe.')->group(function (): void {
    Route::get('/{token}', [UnsubscribeController::class, 'show'])->where('token', '[A-Za-z0-9_\-]+')->name('show');
    Route::post('/{token}', [UnsubscribeController::class, 'store'])->where('token', '[A-Za-z0-9_\-]+')->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class, \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])->name('store');
    Route::post('/{token}/anmelden', [UnsubscribeController::class, 'resubscribe'])->where('token', '[A-Za-z0-9_\-]+')->name('resubscribe');
});
