<?php

namespace App\Modules\Seminars;

use App\Modules\Seminars\Console\NotifyTeamDeadlines;
use App\Modules\Seminars\Console\NumberExistingSeminars;
use App\Modules\Seminars\Console\PruneApplicants;
use App\Modules\Seminars\Console\PruneDocuments;
use Illuminate\Support\ServiceProvider;

class SeminarsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Nextcloud: echte Anbindung nur mit Zugangsdaten in der Umgebung, sonst die Variante, die „nicht eingerichtet“ meldet
        $this->app->bind(\App\Modules\Seminars\Nextcloud\NextcloudClient::class, function (): \App\Modules\Seminars\Nextcloud\NextcloudClient {
            $config = (array) config('services.nextcloud');
            $client = new \App\Modules\Seminars\Nextcloud\HttpNextcloudClient((string) ($config['url'] ?? ''), (string) ($config['user'] ?? ''), (string) ($config['password'] ?? ''), (int) ($config['timeout'] ?? 20));

            return $client->enabled() ? $client : new \App\Modules\Seminars\Nextcloud\NullNextcloudClient;
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');

        // Auch bei Aufrufen über den Browser (Systemsteuerung, Einrichtung) müssen die Befehle bekannt sein, nicht nur auf der Konsole
        $this->commands([NotifyTeamDeadlines::class, NumberExistingSeminars::class, PruneApplicants::class, PruneDocuments::class, \App\Modules\Seminars\Console\RemindReports::class, \App\Modules\Seminars\Console\PruneRequests::class, \App\Modules\Seminars\Console\NotifyResultsExpiry::class]);
    }
}
