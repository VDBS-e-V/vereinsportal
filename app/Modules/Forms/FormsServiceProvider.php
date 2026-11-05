<?php

namespace App\Modules\Forms;

use Illuminate\Support\ServiceProvider;

class FormsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
        \Illuminate\Support\Facades\Event::listen(\App\Modules\Access\Events\AccountAnonymizing::class, \App\Modules\Forms\Listeners\AnonymizeFormsOnAccountDeletion::class);

        if ($this->app->runningInConsole()) {
            $this->commands([\App\Modules\Forms\Console\PruneForms::class]);
        }
    }
}
