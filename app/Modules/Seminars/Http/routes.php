<?php

use App\Modules\Seminars\Http\Controllers\EnterSeminarController;
use App\Modules\Seminars\Http\Controllers\FeedbackController;
use App\Modules\Seminars\Http\Controllers\LeadSeminarController;
use App\Modules\Seminars\Http\Controllers\OfficeSeminarController;
use App\Modules\Seminars\Http\Controllers\PaymentOrderController;
use App\Modules\Seminars\Http\Controllers\PlanSeminarController;
use App\Modules\Seminars\Http\Controllers\SchoolSeminarController;
use App\Modules\Seminars\Http\Controllers\SeminarDocumentController;
use App\Modules\Seminars\Http\Controllers\SeminarFilesController;
use App\Modules\Seminars\Http\Controllers\TeamSeminarController;
use Illuminate\Support\Facades\Route;

/* Bereich Schulen: eigene Seminaranfragen (nur die der eigenen Schule). */
Route::middleware(['web', 'auth', 'can:school.area'])->prefix('schulen/seminare')->name('school.seminars.')->group(function (): void {
    Route::get('/', [SchoolSeminarController::class, 'index'])->name('index');
    Route::get('/neu', [SchoolSeminarController::class, 'create'])->name('create');
    Route::post('/', [SchoolSeminarController::class, 'store'])->middleware('throttle:10,10')->name('store');
    Route::get('/{seminar}', [SchoolSeminarController::class, 'show'])->whereNumber('seminar')->name('show');
    Route::post('/{seminar}/rueckmeldung', [FeedbackController::class, 'store'])->whereNumber('seminar')->middleware('throttle:10,10')->name('feedback');
    Route::post('/{seminar}/vertrag', [SchoolSeminarController::class, 'contract'])->whereNumber('seminar')->middleware('throttle:10,10')->name('contract');
});

/* Geschäftsstelle: alle Seminare, Prüfung, Ablehnung. */
Route::middleware(['web', 'auth', 'can:seminars.review'])->prefix('geschaeftsstelle/seminare')->name('seminars.office.')->group(function (): void {
    Route::get('/', [OfficeSeminarController::class, 'index'])->name('index');
    Route::get('/{seminar}', [OfficeSeminarController::class, 'show'])->whereNumber('seminar')->name('show');
    Route::post('/{seminar}/freigabe', [OfficeSeminarController::class, 'release'])->whereNumber('seminar')->middleware('throttle:20,10')->name('release');
    Route::post('/{seminar}/vertrag', [OfficeSeminarController::class, 'contract'])->whereNumber('seminar')->middleware('throttle:10,10')->name('contract');
    Route::post('/{seminar}/status', [OfficeSeminarController::class, 'status'])->whereNumber('seminar')->middleware('throttle:60,10')->name('status');
});

/* Koordination und Geschäftsstelle: Anfrage im Namen einer Schule eintragen. */
Route::middleware(['web', 'auth', 'can:seminars.enter'])->prefix('seminare/anfrage-eintragen')->name('seminars.enter.')->group(function (): void {
    Route::get('/', [EnterSeminarController::class, 'create'])->name('create');
    Route::post('/', [EnterSeminarController::class, 'store'])->middleware('throttle:20,10')->name('store');
});

/* Koordination: planen, ausschreiben, absagen. */
Route::middleware(['web', 'auth', 'can:seminars.plan'])->prefix('koordination/seminare')->name('seminars.plan.')->group(function (): void {
    Route::get('/', [PlanSeminarController::class, 'index'])->name('index');
    Route::get('/rueckmeldungen', [FeedbackController::class, 'index'])->name('feedback');
    Route::get('/{seminar}', [PlanSeminarController::class, 'show'])->whereNumber('seminar')->name('show');
    Route::post('/{seminar}/planen', [PlanSeminarController::class, 'plan'])->whereNumber('seminar')->middleware('throttle:30,10')->name('plan');
    Route::post('/{seminar}/ausschreiben', [PlanSeminarController::class, 'tender'])->whereNumber('seminar')->middleware('throttle:30,10')->name('tender');
    Route::post('/{seminar}/team/{application}/entscheidung', [PlanSeminarController::class, 'decide'])->whereNumber(['seminar', 'application'])->middleware('throttle:120,10')->name('decide');
    Route::post('/{seminar}/team/bestaetigen', [PlanSeminarController::class, 'confirmTeam'])->whereNumber('seminar')->middleware('throttle:20,10')->name('confirm-team');
    Route::post('/{seminar}/team/{application}/nachruecken', [PlanSeminarController::class, 'promote'])->whereNumber(['seminar', 'application'])->middleware('throttle:30,10')->name('promote');
    Route::post('/{seminar}/team/{application}/ausscheiden', [PlanSeminarController::class, 'drop'])->whereNumber(['seminar', 'application'])->middleware('throttle:30,10')->name('drop');
    Route::post('/{seminar}/team/leitung', [PlanSeminarController::class, 'lead'])->whereNumber('seminar')->middleware('throttle:30,10')->name('lead');
    Route::post('/{seminar}/onboarding', [PlanSeminarController::class, 'onboarding'])->whereNumber('seminar')->middleware('throttle:20,10')->name('onboarding');
    Route::post('/{seminar}/briefing', [PlanSeminarController::class, 'briefing'])->whereNumber('seminar')->middleware('throttle:20,10')->name('briefing');
    Route::post('/{seminar}/datei', [PlanSeminarController::class, 'upload'])->whereNumber('seminar')->middleware('throttle:20,10')->name('upload');
    Route::post('/{seminar}/vorbereitet', [PlanSeminarController::class, 'prepare'])->whereNumber('seminar')->middleware('throttle:20,10')->name('prepare');
    Route::post('/{seminar}/bericht-zurueck', [PlanSeminarController::class, 'returnReport'])->whereNumber('seminar')->middleware('throttle:20,10')->name('return-report');
    Route::post('/{seminar}/ergebnisse-senden', [PlanSeminarController::class, 'sendResults'])->whereNumber('seminar')->middleware('throttle:20,10')->name('send-results');
    Route::post('/{seminar}/ergebnisse-verlaengern', [PlanSeminarController::class, 'extendResults'])->whereNumber('seminar')->middleware('throttle:20,10')->name('extend-results');
    Route::post('/{seminar}/nextcloud', [PlanSeminarController::class, 'folder'])->whereNumber('seminar')->middleware('throttle:20,10')->name('folder');
    Route::post('/{seminar}/abschliessen', [PlanSeminarController::class, 'complete'])->whereNumber('seminar')->middleware('throttle:20,10')->name('complete');
    Route::post('/{seminar}/absagen', [PlanSeminarController::class, 'cancel'])->whereNumber('seminar')->middleware('throttle:30,10')->name('cancel');
});

/* Teamende: ausgeschriebene Seminare und Interesse melden. */
Route::middleware(['web', 'auth', 'can:seminars.apply'])->prefix('teamende/seminare')->name('seminars.team.')->group(function (): void {
    Route::get('/', [TeamSeminarController::class, 'index'])->name('index');
    Route::post('/{seminar}/melden', [TeamSeminarController::class, 'apply'])->whereNumber('seminar')->middleware('throttle:30,10')->name('apply');
    Route::delete('/{seminar}/melden', [TeamSeminarController::class, 'withdraw'])->whereNumber('seminar')->middleware('throttle:30,10')->name('withdraw');
});

/* Seminarleitung: nur das eigene Seminar (die Prüfung steht im Controller). */
Route::middleware(['web', 'auth', 'can:seminars.apply'])->prefix('teamende/leitung')->name('seminars.lead.')->group(function (): void {
    Route::get('/', [LeadSeminarController::class, 'index'])->name('index');
    Route::get('/{seminar}', [LeadSeminarController::class, 'show'])->whereNumber('seminar')->name('show');
    Route::post('/{seminar}/durchgefuehrt', [LeadSeminarController::class, 'held'])->whereNumber('seminar')->middleware('throttle:20,10')->name('held');
    Route::post('/{seminar}/datei', [LeadSeminarController::class, 'upload'])->whereNumber('seminar')->middleware('throttle:20,10')->name('upload');
    Route::post('/{seminar}/vertrag', [LeadSeminarController::class, 'contract'])->whereNumber('seminar')->middleware('throttle:20,10')->name('contract');
    Route::post('/{seminar}/abgeben', [LeadSeminarController::class, 'submit'])->whereNumber('seminar')->middleware('throttle:20,10')->name('submit');
});

/* Geschäftsstelle (Buchhaltung): Zahlungsaufträge. */
Route::middleware(['web', 'auth', 'can:seminars.payments'])->prefix('geschaeftsstelle/zahlungsauftraege')->name('seminars.payments.')->group(function (): void {
    Route::get('/', [PaymentOrderController::class, 'index'])->name('index');
    Route::get('/export', [PaymentOrderController::class, 'export'])->name('export');
    Route::post('/{order}', [PaymentOrderController::class, 'decide'])->whereNumber('order')->middleware('throttle:60,10')->name('decide');
});

/* Seminarmappe (ZIP) und Druckplaner; wer sie nutzen darf, prüft PrintPlans::canUse. */
Route::middleware(['web', 'auth'])->prefix('seminare/{seminar}')->name('seminars.files.')->group(function (): void {
    Route::get('/druckplaner', [SeminarFilesController::class, 'planner'])->name('planner');
    Route::put('/druckplaner', [SeminarFilesController::class, 'save'])->middleware('throttle:30,10')->name('save');
    Route::get('/druckplaner/pdf', [SeminarFilesController::class, 'pdf'])->middleware('throttle:20,10')->name('pdf');
    Route::get('/mappe', [SeminarFilesController::class, 'folder'])->middleware('throttle:20,10')->name('folder');
});

/* Dateien zu Seminaren (Zugriff je Dokument in SeminarDocuments::canDownload). */
Route::middleware(['web', 'auth'])->get('/seminare/dokument/{document}', [SeminarDocumentController::class, 'download'])->whereNumber('document')->middleware('throttle:60,1')->name('seminars.documents.download');
