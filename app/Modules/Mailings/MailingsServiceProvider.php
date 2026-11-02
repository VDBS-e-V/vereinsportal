<?php

namespace App\Modules\Mailings;

use Illuminate\Support\ServiceProvider;

class MailingsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
    }
}
