<?php

use App\Modules\Letters\Http\Controllers\LetterController;
use App\Modules\Letters\Http\Controllers\LetterTemplateController;
use Illuminate\Support\Facades\Route;

/*
 * Briefvorlagen: Word-Dateien mit Platzhaltern. Ansehen, Herunterladen und Erzeugen mit letters.use (Koordination, Geschäftsstelle, Vorstand),
 * Hochladen, Ändern und Löschen nur mit letters.manage (Geschäftsstelle, Vorstand).
 */
Route::middleware(['web', 'auth'])->group(function (): void {
    Route::prefix('koordination/briefvorlagen')->name('letters.')->middleware('can:letters.use')->group(function (): void {
        Route::get('/', [LetterTemplateController::class, 'index'])->name('index');
        Route::get('/platzhalter', [LetterTemplateController::class, 'variables'])->name('variables');
        Route::get('/platzhalter/datei', [LetterTemplateController::class, 'variablesFile'])->name('variables-file');
        Route::get('/beispiel', [LetterTemplateController::class, 'sample'])->name('sample');
        Route::get('/beispiele-alle', [LetterTemplateController::class, 'samplesZip'])->name('samples-zip');
        Route::get('/beispiele/{key}', [LetterTemplateController::class, 'sampleFile'])->where('key', '[a-z\-]+')->name('sample-file');

        Route::middleware('can:letters.manage')->group(function (): void {
            Route::get('/neu', [LetterTemplateController::class, 'create'])->name('create');
            Route::post('/', [LetterTemplateController::class, 'store'])->middleware('throttle:20,10')->name('store');
            Route::post('/beispiele-uebernehmen', [LetterTemplateController::class, 'installSamples'])->middleware('throttle:10,10')->name('samples-install');
            Route::post('/loeschen', [LetterTemplateController::class, 'destroyMany'])->name('destroy-many');
            Route::get('/{template}/bearbeiten', [LetterTemplateController::class, 'edit'])->whereNumber('template')->name('edit');
            Route::put('/{template}', [LetterTemplateController::class, 'update'])->whereNumber('template')->middleware('throttle:20,10')->name('update');
            Route::delete('/{template}', [LetterTemplateController::class, 'destroy'])->whereNumber('template')->name('destroy');
        });

        Route::get('/{template}/datei', [LetterTemplateController::class, 'download'])->whereNumber('template')->name('download');
    });

    Route::post('seminare/{seminar}/schreiben', [LetterController::class, 'generate'])->whereNumber('seminar')->middleware(['can:letters.use', 'throttle:30,10'])->name('letters.generate');
});
