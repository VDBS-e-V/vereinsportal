<?php

use App\Modules\Mailings\Http\Controllers\DeliveryQualityController;
use App\Modules\Mailings\Http\Controllers\GroupMailingController;
use App\Modules\Mailings\Http\Controllers\MailingAttachmentController;
use App\Modules\Mailings\Http\Controllers\MailingProgressController;
use App\Modules\Mailings\Http\Controllers\MailSnippetController;
use App\Modules\Mailings\Http\Controllers\MailWebhookController;
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
foreach (['board' => ['vorstand/mitteilungen', 'mailings.members'], 'office' => ['geschaeftsstelle/mitteilungen', 'mailings.send'], 'coordination' => ['koordination/rundschreiben', 'mailings.send_limited']] as $name => [$prefix, $ability]) {
    Route::middleware(['web', 'auth', 'can:'.$ability])->prefix($prefix)->name('mailings.'.$name.'.')->group(function (): void {
        Route::get('/', [GroupMailingController::class, 'index'])->name('index');
        Route::get('/neu', [GroupMailingController::class, 'create'])->name('create');
        Route::post('/vorschau', [GroupMailingController::class, 'preview'])->name('preview');
        Route::post('/test', [GroupMailingController::class, 'test'])->middleware('throttle:10,10')->name('test');
        Route::post('/', [GroupMailingController::class, 'store'])->middleware('throttle:6,10')->name('store');
        Route::post('/entwurf', [GroupMailingController::class, 'draft'])->middleware('throttle:30,10')->name('draft');
        Route::get('/personen', [GroupMailingController::class, 'people'])->middleware('throttle:60,1')->name('people');
        Route::get('/gesperrt', [SuppressionController::class, 'index'])->name('suppressions');
        Route::post('/gesperrt', [SuppressionController::class, 'store'])->middleware('throttle:20,10')->name('suppressions.store');
        Route::post('/gesperrt/{suppression}/freigeben', [SuppressionController::class, 'release'])->whereNumber('suppression')->name('suppressions.release');
        Route::get('/{mailing}', [MailingProgressController::class, 'show'])->whereNumber('mailing')->name('show');
        Route::get('/{mailing}/bescheinigung', [MailingProgressController::class, 'certificate'])->whereNumber('mailing')->name('certificate');
        Route::post('/{mailing}/abbrechen', [MailingProgressController::class, 'cancel'])->whereNumber('mailing')->name('cancel');
        Route::post('/{mailing}/wiederholen', [MailingProgressController::class, 'retry'])->whereNumber('mailing')->name('retry');
        Route::post('/{mailing}/nachfassen', [MailingProgressController::class, 'followUp'])->middleware('throttle:10,10')->whereNumber('mailing')->name('followup');
        Route::post('/{mailing}/anhaenge', [MailingAttachmentController::class, 'store'])->middleware('throttle:30,10')->whereNumber('mailing')->name('attachments.store');
        Route::get('/{mailing}/anhaenge/{attachment}', [MailingAttachmentController::class, 'download'])->whereNumber(['mailing', 'attachment'])->name('attachments.download');
        Route::delete('/{mailing}/anhaenge/{attachment}', [MailingAttachmentController::class, 'destroy'])->whereNumber(['mailing', 'attachment'])->name('attachments.destroy');
        Route::post('/{mailing}/freigeben', [MailingProgressController::class, 'approve'])->whereNumber('mailing')->name('approve');
        Route::post('/{mailing}/zurueckgeben', [MailingProgressController::class, 'reject'])->whereNumber('mailing')->name('reject');
        Route::post('/{mailing}/zurueckziehen', [MailingProgressController::class, 'withdraw'])->whereNumber('mailing')->name('withdraw');
        Route::delete('/{mailing}', [MailingProgressController::class, 'destroy'])->whereNumber('mailing')->name('destroy');
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
    Route::get('/bausteine', [MailSnippetController::class, 'index'])->name('snippets');
    Route::post('/bausteine', [MailSnippetController::class, 'store'])->name('snippets.store');
    Route::post('/bausteine/loeschen', [MailSnippetController::class, 'destroyMany'])->name('snippets.destroy-many');
    Route::delete('/bausteine/{snippet}', [MailSnippetController::class, 'destroy'])->whereNumber('snippet')->name('snippets.destroy');
    Route::get('/seminar', [MailTemplateController::class, 'team'])->name('team');
    Route::put('/seminar', [MailTemplateController::class, 'updateTeam'])->name('team.update');
    Route::delete('/seminar', [MailTemplateController::class, 'resetTeam'])->name('team.reset');
    Route::get('/{template}/bearbeiten', [MailTemplateController::class, 'edit'])->whereNumber('template')->name('edit');
    Route::put('/{template}', [MailTemplateController::class, 'update'])->whereNumber('template')->name('update');
    Route::post('/{template}/versionen/{version}', [MailTemplateController::class, 'restore'])->whereNumber(['template', 'version'])->name('restore');
    Route::post('/loeschen', [MailTemplateController::class, 'destroyMany'])->name('destroy-many');
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

Route::middleware(['web', 'auth', 'can:mail_templates.manage'])->prefix('geschaeftsstelle/zustellqualitaet')->name('mailings.quality.')->group(function (): void {
    Route::get('/', [DeliveryQualityController::class, 'index'])->name('index');
    Route::post('/pruefen', [DeliveryQualityController::class, 'check'])->middleware('throttle:10,1')->name('check');
});

// Rückmeldungen des Versanddienstes: ohne Sitzung und ohne CSRF, gesichert durch die Signatur im Kopf X-Signature
Route::middleware('throttle:120,1')->post('/webhooks/mail', MailWebhookController::class)->name('mailings.webhook');
