<?php

namespace App\Modules\Seminars\Console;

use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Services\SeminarNotifications;
use Illuminate\Console\Command;

/** Erinnert die Seminarleitung drei Tage vor dem Stichtag und meldet Verzug an Seminarleitung und Koordination (je einmal). */
class RemindReports extends Command
{
    protected $signature = 'seminars:remind-reports {--dry-run : Nur zählen, nichts senden}';

    protected $description = 'Erinnert an Bericht und Verträge durchgeführter Seminare';

    public function handle(SeminarNotifications $notifications): int
    {
        $soon = Seminar::query()->with(['lead.person', 'partner'])->where('status', SeminarStatus::Held->value)->whereNull('report_reminded_at')
            ->whereBetween('report_due_on', [today()->toDateString(), today()->addDays(3)->toDateString()])->get();
        $late = Seminar::query()->with(['lead.person', 'partner'])->where('status', SeminarStatus::Held->value)->whereNull('report_overdue_notified_at')
            ->where('report_due_on', '<', today()->toDateString())->get();

        if (! $this->option('dry-run')) {
            foreach ($soon as $seminar) {
                $notifications->reportDue($seminar);
                $seminar->forceFill(['report_reminded_at' => now()])->save();
            }

            foreach ($late as $seminar) {
                $notifications->reportOverdue($seminar);
                $seminar->forceFill(['report_overdue_notified_at' => now()])->save();
            }
        }

        $this->info('Erinnerungen: '.$soon->count().', Verzug: '.$late->count());

        return self::SUCCESS;
    }
}
