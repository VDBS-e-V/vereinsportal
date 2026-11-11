<?php

namespace App\Modules\Letters;

use Illuminate\Support\ServiceProvider;

class LettersServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
    }
}
