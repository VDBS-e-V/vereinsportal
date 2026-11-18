<?php

use App\Modules\Seminars\Http\Controllers\OfficeSeminarController;
use App\Modules\Seminars\Http\Controllers\SeminarDocumentController;
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

/* Dateien zu Seminaren (Zugriff je Dokument in SeminarDocuments::canDownload). */
Route::middleware(['web', 'auth'])->get('/seminare/dokument/{document}', [SeminarDocumentController::class, 'download'])->whereNumber('document')->middleware('throttle:60,1')->name('seminars.documents.download');
