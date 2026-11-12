<?php

use App\Modules\Feedback\Http\Controllers\FeedbackEvaluationController;
use App\Modules\Feedback\Http\Controllers\FeedbackFormController;
use App\Modules\Feedback\Http\Controllers\FeedbackLinkController;
use App\Modules\Feedback\Http\Controllers\PublicFeedbackController;
use Illuminate\Support\Facades\Route;

/*
 * Feedback: Formulare (feedback.manage) und Links je Seminar unter /koordination; die Antwortseite /feedback/{token} ist öffentlich und anonym.
 */
Route::middleware(['web', 'auth', 'can:feedback.manage'])->group(function (): void {
    Route::prefix('koordination/feedback')->name('feedback-forms.')->group(function (): void {
        Route::get('/', [FeedbackFormController::class, 'index'])->name('index');
        Route::get('/{form}', [FeedbackFormController::class, 'edit'])->whereNumber('form')->name('edit');
        Route::get('/{form}/vorschau', [FeedbackFormController::class, 'preview'])->whereNumber('form')->name('preview');
        Route::put('/{form}', [FeedbackFormController::class, 'update'])->whereNumber('form')->name('update');
        Route::post('/{form}/zuruecksetzen', [FeedbackFormController::class, 'reset'])->whereNumber('form')->name('reset');
    });

    Route::prefix('koordination/seminare/{seminar}/feedback')->where(['seminar' => '[0-9]+'])->name('seminars.feedback.')->group(function (): void {
        Route::post('/', [FeedbackLinkController::class, 'store'])->middleware('throttle:30,10')->name('store');
        Route::post('/beenden', [FeedbackLinkController::class, 'close'])->name('close');
        Route::get('/{link}/aushang', [FeedbackLinkController::class, 'print'])->whereNumber('link')->name('print');
    });
});

Route::middleware(['web', 'auth', 'can:feedback.view'])->group(function (): void {
    Route::prefix('koordination/feedback-auswertung')->name('feedback-eval.')->group(function (): void {
        Route::get('/', [FeedbackEvaluationController::class, 'index'])->name('index');
        Route::get('/export', [FeedbackEvaluationController::class, 'export'])->name('export');
        Route::get('/seminar/{seminar}', [FeedbackEvaluationController::class, 'seminar'])->whereNumber('seminar')->name('seminar');
        Route::post('/seminar/{seminar}/anonymisieren', [FeedbackEvaluationController::class, 'anonymize'])->whereNumber('seminar')->middleware(['can:feedback.manage', 'throttle:20,10'])->name('anonymize');
    });
});

Route::middleware('web')->prefix('feedback')->name('feedback.')->group(function (): void {
    Route::get('/danke', [PublicFeedbackController::class, 'thanks'])->name('thanks');
    Route::get('/{token}', [PublicFeedbackController::class, 'show'])->where('token', '[A-Za-z0-9]{20,64}')->middleware('throttle:60,1')->name('show');
    Route::post('/{token}', [PublicFeedbackController::class, 'store'])->where('token', '[A-Za-z0-9]{20,64}')->middleware(['throttle:30,1', 'bot.guard'])->name('store');
});
