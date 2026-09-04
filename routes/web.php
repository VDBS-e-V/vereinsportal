<?php

use App\Modules\Identity\Http\Controllers\ConfirmEmailChangeController;
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
        Route::get(
            '/email/aenderung/bestaetigen/{publicId}',
            ConfirmEmailChangeController::class,
        )
            ->middleware('signed')
            ->name(
                'identity.email-change.verify'
            );

        Volt::route(
            '/email/aenderung/sicherheit/{publicId}',
            'identity.email-change-security',
        )
            ->middleware('signed')
            ->name(
                'identity.email-change.security'
            );

        Volt::route(
            '/anmelden',
            'identity.login',
        )->name('my.login');

        Volt::route(
            '/anmeldung/2fa',
            'identity.two-factor-challenge',
        )->name('my.two-factor.challenge');

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
                '/profil',
                'identity.profile',
            )->name('my.profile');

            Volt::route(
                '/profil/email',
                'identity.email-change',
            )->name('my.email-change');

            Volt::route(
                '/profil/passwort',
                'identity.password-change',
            )->name('my.password.change');

            Volt::route(
                '/profil/sicherheit',
                'identity.security',
            )->name('my.security');
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
