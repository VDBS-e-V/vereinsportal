<?php

namespace App\Modules\ContactForms;

use Illuminate\Support\ServiceProvider;

class ContactFormsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
    }
}
