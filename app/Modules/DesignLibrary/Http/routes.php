<?php

use App\Modules\DesignLibrary\Http\DesignLibraryController;
use Illuminate\Support\Facades\Route;

// Zugriff: Berechtigung design.view (Administration und Vorstand, siehe AccessGovernance\Support\Capabilities).
Route::middleware(['web', 'auth', 'can:design.view'])
    ->prefix('design')
    ->name('design-library.')
    ->group(function (): void {
        Route::get('/', [DesignLibraryController::class, 'index'])->name('index');
        Route::get('/pakete', [DesignLibraryController::class, 'packages'])->name('packages');
        Route::get('/api/platzhalter', [DesignLibraryController::class, 'placeholders'])->name('placeholders');
        Route::get('/{slug}', [DesignLibraryController::class, 'show'])
            ->where('slug', '[a-z0-9-]+')
            ->name('show');
    });
