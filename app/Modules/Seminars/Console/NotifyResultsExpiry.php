<?php

namespace App\Modules\Seminars\Console;

use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Services\SeminarNotifications;
use Illuminate\Console\Command;

/** Weist die Koordination 30 Tage vor dem Ablauf eines Ergebnislinks einmal darauf hin. Gelöscht wird nichts von selbst. */
class NotifyResultsExpiry extends Command
{
    protected $signature = 'seminars:results-expiry {--dry-run : Nur zählen, nichts senden}';

    protected $description = 'Weist auf bald ablaufende Ergebnislinks hin';

    public function handle(SeminarNotifications $notifications): int
    {
        $due = Seminar::query()->with('partner')->whereNotNull('results_url')->whereNull('results_expiry_notified_at')
            ->whereBetween('results_expires_on', [today()->toDateString(), today()->addDays(30)->toDateString()])->get();

        foreach ($due as $seminar) {
            if (! $this->option('dry-run')) {
                $notifications->resultsExpiring($seminar);
                $seminar->forceFill(['results_expiry_notified_at' => now()])->save();
            }
        }

        $this->info('Hinweise auf ablaufende Links: '.$due->count());

        return self::SUCCESS;
    }
}
