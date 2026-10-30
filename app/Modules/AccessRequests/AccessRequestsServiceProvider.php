<?php

namespace App\Modules\AccessRequests;

use App\Modules\Access\Events\AccountAnonymizing;
use App\Modules\AccessRequests\Console\PruneAccessRequests;
use App\Modules\AccessRequests\Listeners\RemoveAccessRequestsOnAccountDeletion;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AccessRequestsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');

        Event::listen(AccountAnonymizing::class, RemoveAccessRequestsOnAccountDeletion::class);

        if ($this->app->runningInConsole()) {
            $this->commands([PruneAccessRequests::class]);
        }
    }
}
