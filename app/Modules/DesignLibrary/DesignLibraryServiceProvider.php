<?php

namespace App\Modules\DesignLibrary;

use Illuminate\Support\ServiceProvider;

class DesignLibraryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
    }
}
