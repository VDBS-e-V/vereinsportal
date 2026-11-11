<?php

use App\Modules\Forms\Http\Controllers\FormApplicationController;
use App\Modules\Forms\Http\Controllers\FormOfficeController;
use Illuminate\Support\Facades\Route;

/*
 * Anträge: eigene Anträge in den Bereichen Teamende, Mitarbeitende und Mitglieder, Bearbeitung unter /geschaeftsstelle/antraege (forms.view; Aktionen prüft der Dienst nach forms.manage/forms.approve).
 */
Route::middleware(['web', 'auth'])->group(function (): void {
    // Eigene Anträge: je Bereich eigene Seiten und passende Formulare (Teamende, Mitarbeitende, Mitglieder).
    foreach (['team' => ['teamende/antraege', 'forms.submit_team'], 'employee' => ['mitarbeitende/antraege', 'forms.submit'], 'member' => ['mitglieder/antraege', 'forms.submit_member']] as $audience => [$path, $can]) {
        Route::prefix($path)->name($audience.'-forms.')->middleware('can:'.$can)->group(function (): void {
            Route::get('/', [FormApplicationController::class, 'index'])->name('index');
            Route::post('/', [FormApplicationController::class, 'store'])->middleware('throttle:30,10')->name('store');
            Route::get('/{application}/bearbeiten', [FormApplicationController::class, 'edit'])->whereNumber('application')->name('edit');
            Route::put('/{application}', [FormApplicationController::class, 'update'])->whereNumber('application')->middleware('throttle:60,10')->name('update');
            Route::post('/{application}/erstattung', [FormApplicationController::class, 'reimburse'])->whereNumber('application')->middleware('throttle:20,10')->name('reimburse');
            Route::get('/{application}', [FormApplicationController::class, 'show'])->whereNumber('application')->name('show');
            Route::get('/{application}/belege/{file}', [FormApplicationController::class, 'file'])->whereNumber(['application', 'file'])->name('file');
        });
    }

    Route::prefix('geschaeftsstelle/antraege')->name('forms-office.')->middleware('can:forms.view')->group(function (): void {
        Route::get('/', [FormOfficeController::class, 'index'])->name('index');
        Route::get('/kostenstellen', [FormOfficeController::class, 'costCenters'])->name('cost-centers');
        Route::post('/kostenstellen', [FormOfficeController::class, 'storeCostCenter'])->middleware('can:forms.manage')->name('cost-centers.store');
        Route::post('/kostenstellen/{center}/umschalten', [FormOfficeController::class, 'toggleCostCenter'])->whereNumber('center')->middleware('can:forms.manage')->name('cost-centers.toggle');
        Route::get('/einstellungen', [FormOfficeController::class, 'settings'])->middleware('can:forms.manage')->name('settings');
        Route::put('/einstellungen', [FormOfficeController::class, 'updateSettings'])->middleware('can:forms.manage')->name('settings.update');
        Route::post('/sammelliste', [FormOfficeController::class, 'exportPayout'])->middleware(['can:forms.manage', 'throttle:20,10'])->name('export');
        Route::get('/buchungsliste', [FormOfficeController::class, 'exportBookings'])->middleware('can:forms.manage')->name('export.bookings');
        Route::get('/jahresuebersicht', [FormOfficeController::class, 'exportSummary'])->middleware('can:forms.manage')->name('export.summary');
        Route::get('/{application}', [FormOfficeController::class, 'show'])->whereNumber('application')->name('show');
        Route::post('/{application}/rechnung', [FormOfficeController::class, 'invoice'])->whereNumber('application')->middleware(['can:forms.manage', 'throttle:20,10'])->name('invoice');
        Route::post('/{application}/aktion', [FormOfficeController::class, 'act'])->whereNumber('application')->middleware('throttle:60,10')->name('act');
        Route::get('/{application}/belege/{file}', [FormApplicationController::class, 'file'])->whereNumber(['application', 'file'])->name('file');
    });
});
