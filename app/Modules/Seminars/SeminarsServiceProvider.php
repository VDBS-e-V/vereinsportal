<?php

namespace App\Modules\Seminars;

use App\Modules\Seminars\Console\RemindDeadlines;
use Illuminate\Support\ServiceProvider;

class SeminarsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands([RemindDeadlines::class]);
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
    }
}
