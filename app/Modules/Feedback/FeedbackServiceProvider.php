<?php

namespace App\Modules\Feedback;

use Illuminate\Support\ServiceProvider;

class FeedbackServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
    }
}
