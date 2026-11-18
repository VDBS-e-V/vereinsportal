<?php

namespace App\Modules\Quotes;

use Illuminate\Support\ServiceProvider;

class QuotesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
    }
}
