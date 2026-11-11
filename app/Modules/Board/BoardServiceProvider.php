<?php

namespace App\Modules\Board;

use Illuminate\Support\ServiceProvider;

class BoardServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
    }
}
