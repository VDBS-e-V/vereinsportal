<?php

namespace App\Modules\Seminars\Console;

use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Services\SeminarNotifications;
use Illuminate\Console\Command;

class NotifyResultsExpiry extends Command
{
    protected $signature = 'seminars:results-expiry';

    protected $description = 'Weist die Koordination darauf hin, dass ein Ergebnislink für Schulen bald abläuft (einmal je Link). Verlängert oder gelöscht wird nach Rückfrage, nie automatisch.';

    public function handle(SeminarNotifications $notices): int
    {
        $until = now()->addDays((int) config('portal.seminars.retention.results_notice_days', 30))->toDateString();
        $count = 0;

        Seminar::query()->whereNotNull('results_share_url')->whereNotNull('results_expires_at')->whereNull('results_expiry_notified_at')->whereDate('results_expires_at', '<=', $until)->each(function (Seminar $seminar) use ($notices, &$count): void {
            $notices->resultsExpiring($seminar);
            $seminar->forceFill(['results_expiry_notified_at' => now()])->save();
            $count++;
        });

        $this->info('Hinweise gesendet: '.$count.'.');

        return self::SUCCESS;
    }
}
