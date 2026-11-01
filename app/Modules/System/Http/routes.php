<?php

use App\Modules\System\Http\Controllers\SystemController;
use App\Modules\System\Http\Controllers\SystemLogController;
use App\Modules\System\Http\Controllers\SystemQueueController;
use App\Modules\System\Http\Controllers\SystemSettingsController;
use Illuminate\Support\Facades\Route;

/*
 * Systemsteuerung im Bereich Admins: Zustand (nur lesen) und Aufgaben (feste Positivliste). Berechtigung system.manage nur für die Rolle Administration.
 */
Route::middleware(['web', 'auth', 'can:system.manage'])->prefix('admins/system')->name('system.')->group(function (): void {
    Route::get('/', [SystemController::class, 'status'])->name('index');
    Route::get('/aufgaben', [SystemController::class, 'tasks'])->name('tasks');
    Route::get('/fehlerprotokoll', [SystemLogController::class, 'index'])->name('logs');
    Route::get('/warteschlange', [SystemQueueController::class, 'index'])->name('queue');
    Route::post('/warteschlange/{uuid}/wiederholen', [SystemQueueController::class, 'retry'])->where('uuid', '[A-Za-z0-9\-]+')->name('queue.retry');
    Route::delete('/warteschlange/{uuid}', [SystemQueueController::class, 'forget'])->where('uuid', '[A-Za-z0-9\-]+')->name('queue.forget');
    Route::delete('/warteschlange', [SystemQueueController::class, 'flush'])->name('queue.flush');
    Route::get('/einstellungen', [SystemSettingsController::class, 'index'])->name('settings');
    Route::post('/einstellungen/testmail', [SystemSettingsController::class, 'testMail'])->name('settings.test-mail');
    Route::post('/aufgaben/{task}', [SystemController::class, 'run'])->where('task', '[a-z0-9\-]+')->name('tasks.run');
});
