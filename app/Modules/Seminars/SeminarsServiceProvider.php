<?php

namespace App\Modules\Seminars;

use Illuminate\Support\ServiceProvider;

class SeminarsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
    }
}
