<?php

namespace App\Modules\NavigationControl;

use Illuminate\Support\ServiceProvider;

class NavigationControlServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
    }
}
