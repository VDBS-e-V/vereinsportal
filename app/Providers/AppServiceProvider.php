<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Passwort-Regel für das ganze Portal: mindestens 12 Zeichen mit Groß- und Kleinbuchstaben und einer Ziffer
        Password::defaults(fn (): Password => Password::min(12)->mixedCase()->numbers());
    }
}
