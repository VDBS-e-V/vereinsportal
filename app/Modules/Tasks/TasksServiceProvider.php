<?php

namespace App\Modules\Tasks;

use Illuminate\Support\ServiceProvider;

class TasksServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
    }
}
