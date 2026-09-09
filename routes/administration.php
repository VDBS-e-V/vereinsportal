<?php

use App\Modules\Administration\Http\Controllers\AssignUserRoleController;
use App\Modules\Administration\Http\Controllers\EndUserRoleController;
use App\Modules\Administration\Http\Controllers\HomeController;
use App\Modules\Administration\Http\Controllers\UpdateUserStatusController;
use App\Modules\Administration\Http\Controllers\UserIndexController;
use App\Modules\Administration\Http\Controllers\UserShowController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'web',
    'auth',
    'identity.revalidate',
    'administration.access',
])
    ->domain(config('domains.my'))
    ->prefix('verwaltung')
    ->name('administration.')
    ->group(function (): void {
        Route::get(
            '/',
            HomeController::class,
        )->name('home');

        Route::get(
            '/benutzer',
            UserIndexController::class,
        )->name('users.index');

        Route::get(
            '/benutzer/{user}',
            UserShowController::class,
        )
            ->whereNumber('user')
            ->name('users.show');

        Route::post(
            '/benutzer/{user}/status',
            UpdateUserStatusController::class,
        )
            ->whereNumber('user')
            ->name('users.status.update');

        Route::post(
            '/benutzer/{user}/rollen',
            AssignUserRoleController::class,
        )
            ->whereNumber('user')
            ->name('users.roles.assign');

        Route::post(
            '/benutzer/{user}/rollen/{assignment}/beenden',
            EndUserRoleController::class,
        )
            ->whereNumber('user')
            ->whereNumber('assignment')
            ->name('users.roles.end');
    });
