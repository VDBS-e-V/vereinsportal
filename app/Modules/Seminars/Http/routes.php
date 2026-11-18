<?php

use App\Modules\Seminars\Http\Controllers\OfficeSeminarController;
use App\Modules\Seminars\Http\Controllers\SchoolSeminarController;
use Illuminate\Support\Facades\Route;

/* Bereich Schulen: eigene Seminaranfragen (nur die der eigenen Schule). */
Route::middleware(['web', 'auth', 'can:school.area'])->prefix('schulen/seminare')->name('school.seminars.')->group(function (): void {
    Route::get('/', [SchoolSeminarController::class, 'index'])->name('index');
    Route::get('/neu', [SchoolSeminarController::class, 'create'])->name('create');
    Route::post('/', [SchoolSeminarController::class, 'store'])->middleware('throttle:10,10')->name('store');
    Route::get('/{seminar}', [SchoolSeminarController::class, 'show'])->whereNumber('seminar')->name('show');
});

/* Geschäftsstelle: alle Seminare, Prüfung, Ablehnung. */
Route::middleware(['web', 'auth', 'can:seminars.review'])->prefix('geschaeftsstelle/seminare')->name('seminars.office.')->group(function (): void {
    Route::get('/', [OfficeSeminarController::class, 'index'])->name('index');
    Route::get('/{seminar}', [OfficeSeminarController::class, 'show'])->whereNumber('seminar')->name('show');
    Route::post('/{seminar}/status', [OfficeSeminarController::class, 'status'])->whereNumber('seminar')->middleware('throttle:60,10')->name('status');
});
