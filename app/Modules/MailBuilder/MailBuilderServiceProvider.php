<?php

namespace App\Modules\MailBuilder;

use Illuminate\Support\ServiceProvider;

class MailBuilderServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
    }
}
