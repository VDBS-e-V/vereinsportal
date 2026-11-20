<?php

use App\Modules\Quotes\Http\Controllers\OfficeQuoteController;
use App\Modules\Quotes\Http\Controllers\QuotePriceController;
use App\Modules\Quotes\Http\Controllers\SchoolQuoteController;
use Illuminate\Support\Facades\Route;

/* Geschäftsstelle: Kostenvoranschläge zu Seminaren und die Preisliste. */
Route::middleware(['web', 'auth', 'can:seminars.review'])->prefix('geschaeftsstelle/seminare')->name('quotes.office.')->group(function (): void {
    Route::get('/preise', [QuotePriceController::class, 'edit'])->name('prices');
    Route::put('/preise', [QuotePriceController::class, 'update'])->middleware('throttle:30,10')->name('prices.update');
    Route::post('/{seminar}/kva', [OfficeQuoteController::class, 'store'])->whereNumber('seminar')->middleware('throttle:30,10')->name('store');
    Route::get('/{seminar}/kva/{quote}', [OfficeQuoteController::class, 'edit'])->whereNumber(['seminar', 'quote'])->name('edit');
    Route::put('/{seminar}/kva/{quote}', [OfficeQuoteController::class, 'update'])->whereNumber(['seminar', 'quote'])->middleware('throttle:60,10')->name('update');
    Route::post('/{seminar}/kva/{quote}/position', [OfficeQuoteController::class, 'addItem'])->whereNumber(['seminar', 'quote'])->middleware('throttle:60,10')->name('add-item');
    Route::post('/{seminar}/kva/{quote}/senden', [OfficeQuoteController::class, 'send'])->whereNumber(['seminar', 'quote'])->middleware('throttle:20,10')->name('send');
});

/* Interne Freigabe (Vorstand, Koordination). */
Route::middleware(['web', 'auth', 'can:seminars.quote_approve'])->prefix('kva-freigabe')->name('quotes.approval.')->group(function (): void {
    Route::get('/', [OfficeQuoteController::class, 'approvals'])->name('index');
    Route::post('/{quote}', [OfficeQuoteController::class, 'approve'])->whereNumber('quote')->middleware('throttle:30,10')->name('approve');
});

/* PDF: Geschäftsstelle, Freigebende und die Schule des Seminars (nur gesendete Versionen). */
Route::middleware(['web', 'auth'])->get('/kva/{quote}/pdf', [OfficeQuoteController::class, 'pdf'])->whereNumber('quote')->name('quotes.pdf');

/* Bereich Schulen: Antwort auf den Kostenvoranschlag. */
Route::middleware(['web', 'auth', 'can:school.area'])->prefix('schulen/seminare/{seminar}/kva/{quote}')->where(['seminar' => '[0-9]+', 'quote' => '[0-9]+'])->name('school.quotes.')->group(function (): void {
    Route::post('/annehmen', [SchoolQuoteController::class, 'accept'])->middleware('throttle:10,10')->name('accept');
    Route::post('/ablehnen', [SchoolQuoteController::class, 'decline'])->middleware('throttle:10,10')->name('decline');
    Route::post('/rueckfrage', [SchoolQuoteController::class, 'question'])->middleware('throttle:10,10')->name('question');
});
