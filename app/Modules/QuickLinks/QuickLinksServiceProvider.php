<?php

namespace App\Modules\QuickLinks;

use Illuminate\Support\ServiceProvider;

class QuickLinksServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
    }
}
