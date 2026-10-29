<?php

use App\Modules\AccessGovernance\Http\Controllers\RoleAssignmentController;
use App\Modules\AccessGovernance\Http\Controllers\UserAdministrationController;
use Illuminate\Support\Facades\Route;

/*
 * Nutzerverwaltung unter /verwaltung/nutzer. Die Berechtigungen (users.view, users.manage, roles.assign) stehen in
 * Support\Capabilities und werden im AccessGovernanceServiceProvider als Gates registriert.
 */
Route::middleware(['web', 'auth'])->prefix('verwaltung/nutzer')->name('admin.users.')->group(function (): void {
    Route::middleware('can:users.manage')->group(function (): void {
        Route::get('/neu', [UserAdministrationController::class, 'create'])->name('create');
        Route::post('/', [UserAdministrationController::class, 'store'])->name('store');

        Route::post('/{user}/loeschung-abbrechen', [UserAdministrationController::class, 'cancelDeletion'])->whereNumber('user')->name('deletion.cancel');
        Route::post('/{user}/einladung', [UserAdministrationController::class, 'invite'])->whereNumber('user')->name('invite');
        Route::post('/{user}/sperren', [UserAdministrationController::class, 'disable'])->whereNumber('user')->name('disable');
        Route::post('/{user}/entsperren', [UserAdministrationController::class, 'enable'])->whereNumber('user')->name('enable');
        Route::post('/{user}/zwei-faktor-zuruecksetzen', [UserAdministrationController::class, 'resetTwoFactor'])->whereNumber('user')->name('two-factor.reset');
        Route::post('/{user}/passwort-link', [UserAdministrationController::class, 'passwordLink'])->whereNumber('user')->name('password-link');
    });

    Route::middleware('can:roles.assign')->group(function (): void {
        Route::post('/{user}/rollen', [RoleAssignmentController::class, 'store'])->whereNumber('user')->name('roles.store');
        Route::delete('/{user}/rollen/{assignment}', [RoleAssignmentController::class, 'destroy'])->whereNumber(['user', 'assignment'])->name('roles.destroy');
    });

    Route::middleware('can:users.view')->group(function (): void {
        Route::get('/', [UserAdministrationController::class, 'index'])->name('index');
        Route::get('/{user}', [UserAdministrationController::class, 'show'])->whereNumber('user')->name('show');
    });
});
