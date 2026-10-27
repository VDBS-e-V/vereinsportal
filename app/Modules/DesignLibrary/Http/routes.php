<?php

use App\Modules\DesignLibrary\Http\DesignLibraryController;
use Illuminate\Support\Facades\Route;

// Zugriff: nur angemeldet. Rollen-Einschränkung bewusst noch offen
// (später über Access-Governance verfeinerbar).
Route::middleware(['web', 'auth'])
    ->prefix('design')
    ->name('design-library.')
    ->group(function (): void {
        Route::get('/', [DesignLibraryController::class, 'index'])->name('index');
        Route::get('/api/platzhalter', [DesignLibraryController::class, 'placeholders'])->name('placeholders');
        Route::get('/{slug}', [DesignLibraryController::class, 'show'])
            ->where('slug', '[a-z0-9-]+')
            ->name('show');
    });
