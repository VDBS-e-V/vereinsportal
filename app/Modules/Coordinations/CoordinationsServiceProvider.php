<?php

namespace App\Modules\Coordinations;

use Illuminate\Support\ServiceProvider;

class CoordinationsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
    }
}
