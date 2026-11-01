<?php

namespace App\Modules\System;

use Illuminate\Support\ServiceProvider;

class SystemServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
    }
}
