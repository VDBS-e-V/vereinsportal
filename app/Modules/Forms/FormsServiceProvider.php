<?php

namespace App\Modules\Forms;

use Illuminate\Support\ServiceProvider;

class FormsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
    }
}
