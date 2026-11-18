<?php

namespace App\Modules\Seminars;

use App\Modules\Seminars\Console\NotifyResultsExpiry;
use App\Modules\Seminars\Console\PruneRequests;
use App\Modules\Seminars\Console\RemindDeadlines;
use App\Modules\Seminars\Console\RemindReports;
use App\Modules\Seminars\Nextcloud\HttpNextcloudClient;
use App\Modules\Seminars\Nextcloud\NextcloudClient;
use App\Modules\Seminars\Nextcloud\NullNextcloudClient;
use Illuminate\Support\ServiceProvider;

class SeminarsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NextcloudClient::class, function (): NextcloudClient {
            $client = new HttpNextcloudClient((string) config('services.nextcloud.url'), (string) config('services.nextcloud.user'), (string) config('services.nextcloud.password'));

            return $client->isConfigured() ? $client : new NullNextcloudClient;
        });
    }

    public function boot(): void
    {
        $this->commands([NotifyResultsExpiry::class, PruneRequests::class, RemindDeadlines::class, RemindReports::class]);
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
    }
}
