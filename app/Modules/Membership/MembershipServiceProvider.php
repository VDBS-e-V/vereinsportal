<?php

namespace App\Modules\Membership;

use App\Modules\Access\Events\AccountAnonymizing;
use App\Modules\Membership\Console\PruneMemberships;
use App\Modules\Membership\Console\SyncMemberships;
use App\Modules\Membership\Listeners\CleanUpMembershipsOnAccountDeletion;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class MembershipServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');

        Event::listen(AccountAnonymizing::class, CleanUpMembershipsOnAccountDeletion::class);

        // Auch bei Aufrufen über den Browser (Systemsteuerung, Einrichtung) müssen die Befehle bekannt sein, nicht nur auf der Konsole
        $this->commands([SyncMemberships::class, PruneMemberships::class]);
    }
}
