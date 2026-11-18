<?php

use App\Modules\Seminars\Http\Controllers\OfficeSeminarController;
use App\Modules\Seminars\Http\Controllers\PlanSeminarController;
use App\Modules\Seminars\Http\Controllers\SeminarDocumentController;
use App\Modules\Seminars\Http\Controllers\TeamSeminarController;
use App\Modules\Seminars\Http\Controllers\SchoolSeminarController;
use Illuminate\Support\Facades\Route;

/* Bereich Schulen: eigene Seminaranfragen (nur die der eigenen Schule). */
Route::middleware(['web', 'auth', 'can:school.area'])->prefix('schulen/seminare')->name('school.seminars.')->group(function (): void {
    Route::get('/', [SchoolSeminarController::class, 'index'])->name('index');
    Route::get('/neu', [SchoolSeminarController::class, 'create'])->name('create');
    Route::post('/', [SchoolSeminarController::class, 'store'])->middleware('throttle:10,10')->name('store');
    Route::get('/{seminar}', [SchoolSeminarController::class, 'show'])->whereNumber('seminar')->name('show');
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

/* Koordination: planen, ausschreiben, absagen. */
Route::middleware(['web', 'auth', 'can:seminars.plan'])->prefix('koordination/seminare')->name('seminars.plan.')->group(function (): void {
    Route::get('/', [PlanSeminarController::class, 'index'])->name('index');
    Route::get('/{seminar}', [PlanSeminarController::class, 'show'])->whereNumber('seminar')->name('show');
    Route::post('/{seminar}/planen', [PlanSeminarController::class, 'plan'])->whereNumber('seminar')->middleware('throttle:30,10')->name('plan');
    Route::post('/{seminar}/ausschreiben', [PlanSeminarController::class, 'tender'])->whereNumber('seminar')->middleware('throttle:30,10')->name('tender');
    Route::post('/{seminar}/absagen', [PlanSeminarController::class, 'cancel'])->whereNumber('seminar')->middleware('throttle:30,10')->name('cancel');
});

/* Teamende: ausgeschriebene Seminare und Interesse melden. */
Route::middleware(['web', 'auth', 'can:seminars.apply'])->prefix('teamende/seminare')->name('seminars.team.')->group(function (): void {
    Route::get('/', [TeamSeminarController::class, 'index'])->name('index');
    Route::post('/{seminar}/melden', [TeamSeminarController::class, 'apply'])->whereNumber('seminar')->middleware('throttle:30,10')->name('apply');
    Route::delete('/{seminar}/melden', [TeamSeminarController::class, 'withdraw'])->whereNumber('seminar')->middleware('throttle:30,10')->name('withdraw');
});

/* Dateien zu Seminaren (Zugriff je Dokument in SeminarDocuments::canDownload). */
Route::middleware(['web', 'auth'])->get('/seminare/dokument/{document}', [SeminarDocumentController::class, 'download'])->whereNumber('document')->middleware('throttle:60,1')->name('seminars.documents.download');
