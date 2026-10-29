<?php

use App\Modules\Audit\Http\Controllers\AuditController;
use Illuminate\Support\Facades\Route;

/* Protokoll unter /verwaltung/protokoll, Berechtigung audit.view (Support\Capabilities im Modul AccessGovernance). */
Route::middleware(['web', 'auth', 'can:audit.view'])->prefix('verwaltung/protokoll')->name('admin.audit.')->group(function (): void {
    Route::get('/', [AuditController::class, 'index'])->name('index');
    Route::get('/export', [AuditController::class, 'export'])->name('export');
    Route::get('/{entry}', [AuditController::class, 'show'])->whereNumber('entry')->name('show');
});
