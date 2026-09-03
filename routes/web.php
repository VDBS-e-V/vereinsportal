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
            '/registrieren',
            'identity.registration',
        )->name('my.registration.create');

        Volt::route(
            '/registrierung/status/{publicId}',
            'identity.registration-status',
        )->name('my.registration.status');
    });