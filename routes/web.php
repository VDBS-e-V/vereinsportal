<?php

use App\Modules\Identity\Http\Controllers\VerifyRegistrationController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get(
    '/identity/registration/verify/{publicId}/{version}',
    VerifyRegistrationController::class,
)
    ->middleware('signed')
    ->name('identity.registration.verify');

Route::domain(config('domains.my'))
    ->group(function (): void {
        Volt::route(
            '/anmelden',
            'identity.login',
        )->name('my.login');

        Volt::route(
            '/passwort/vergessen',
            'identity.password-forgot',
        )->name('my.password.request');

        Volt::route(
            '/passwort/zuruecksetzen/{token}',
            'identity.password-reset',
        )
            ->middleware('signed')
            ->name('my.password.reset');

        Route::middleware([
            'auth',
            'identity.revalidate',
        ])->group(function (): void {
            Volt::route(
                '/',
                'identity.home',
            )->name('my.home');

            Volt::route(
                '/profil/passwort',
                'identity.password-change',
            )->name('my.password.change');
        });

        Volt::route(
            '/registrieren',
            'identity.registration',
        )->name('my.registration.create');

        Volt::route(
            '/registrierung/status/{publicId}',
            'identity.registration-status',
        )->name('my.registration.status');
    });