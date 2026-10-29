<?php

namespace App\Modules\Partners;

use Illuminate\Support\ServiceProvider;

class PartnersServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
    }
}
