<?php

namespace App\Modules\Contacts;

use Illuminate\Support\ServiceProvider;

/** Modul: Contacts. Importierte Mailingliste (Teamkontakte) und der persönliche Zugang für Bewerbungen ohne Konto. */
class ContactsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
    }
}
