<?php

use App\Modules\ContactForms\Http\Controllers\ContactFlowController;
use App\Modules\ContactForms\Http\Controllers\ContactFormAdminController;
use App\Modules\ContactForms\Http\Controllers\ContactTopicAdminController;
use Illuminate\Support\Facades\Route;

/*
 * Öffentlich (Bereich Start): /kontakt (Themenwahl mit Bereichen und Suche), /kontakt/thema/{id} (Ziel eines Themas), /kontakt/formular/{slug} (Angaben und Anfrage).
 * Verwaltung (Geschäftsstelle): Themen, FAQ-Seiten und Formulare unter /verwaltung/kontakt mit der Berechtigung contact_forms.manage.
 */
Route::middleware('web')->prefix('kontakt')->group(function (): void {
    Route::get('/', [ContactFlowController::class, 'index'])->name('portal.contact');
    Route::get('/thema/{topic}', [ContactFlowController::class, 'topic'])->whereNumber('topic')->name('portal.contact.topic');
    Route::get('/formular/{form:slug}', [ContactFlowController::class, 'form'])->name('portal.contact.form');
    Route::post('/formular/{form:slug}', [ContactFlowController::class, 'send'])->middleware(['bot.guard', 'throttle:5,10'])->name('portal.contact.send');
});

Route::middleware(['web', 'auth', 'can:contact_forms.manage'])->prefix('verwaltung/kontakt')->name('contact-admin.')->group(function (): void {
    Route::get('/', [ContactTopicAdminController::class, 'index'])->name('topics.index');
    Route::get('/themen/neu', [ContactTopicAdminController::class, 'create'])->name('topics.create');
    Route::post('/themen', [ContactTopicAdminController::class, 'store'])->middleware('throttle:60,10')->name('topics.store');
    Route::post('/themen/aktion', [ContactTopicAdminController::class, 'bulk'])->middleware('throttle:60,10')->name('topics.bulk');
    Route::get('/themen/{topic}/bearbeiten', [ContactTopicAdminController::class, 'edit'])->whereNumber('topic')->name('topics.edit');
    Route::put('/themen/{topic}', [ContactTopicAdminController::class, 'update'])->whereNumber('topic')->middleware('throttle:60,10')->name('topics.update');
    Route::delete('/themen/{topic}', [ContactTopicAdminController::class, 'destroy'])->whereNumber('topic')->name('topics.destroy');

    Route::get('/formulare', [ContactFormAdminController::class, 'index'])->name('forms.index');
    Route::get('/formulare/neu', [ContactFormAdminController::class, 'create'])->name('forms.create');
    Route::post('/formulare', [ContactFormAdminController::class, 'store'])->middleware('throttle:60,10')->name('forms.store');
    Route::post('/formulare/loeschen', [ContactFormAdminController::class, 'destroyMany'])->name('forms.destroy-many');
    Route::get('/formulare/{form}/bearbeiten', [ContactFormAdminController::class, 'edit'])->whereNumber('form')->name('forms.edit');
    Route::put('/formulare/{form}', [ContactFormAdminController::class, 'update'])->whereNumber('form')->middleware('throttle:60,10')->name('forms.update');
    Route::post('/formulare/{form}/standard', [ContactFormAdminController::class, 'makeDefault'])->whereNumber('form')->name('forms.default');
    Route::delete('/formulare/{form}', [ContactFormAdminController::class, 'destroy'])->whereNumber('form')->name('forms.destroy');
});
