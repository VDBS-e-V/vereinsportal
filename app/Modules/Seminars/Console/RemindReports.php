<?php

namespace App\Modules\Seminars\Console;

use App\Modules\Seminars\Enums\ApplicationStatus;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Services\SeminarNotifications;
use Illuminate\Console\Command;

class RemindReports extends Command
{
    protected $signature = 'seminars:remind-reports';

    protected $description = 'Erinnert Seminarleitungen drei Tage vor dem Stichtag an Verträge und Bericht und meldet der Koordination überfällige Berichte (je einmal).';

    public function handle(SeminarNotifications $notices): int
    {
        $reminded = 0;
        $overdue = 0;

        Seminar::query()->where('status', SeminarStatus::Held->value)->whereNotNull('report_due_at')->each(function (Seminar $seminar) use ($notices, &$reminded, &$overdue): void {
            $today = now()->startOfDay();

            if ($seminar->report_overdue_notified_at === null && $seminar->report_due_at->lt($today)) {
                $notices->reportOverdue($seminar);
                $seminar->forceFill(['report_overdue_notified_at' => now()])->save();
                $overdue++;
            }

            if ($seminar->report_reminded_at === null && $seminar->report_due_at->lte($today->copy()->addDays(3))) {
                $lead = $seminar->applications()->where('status', ApplicationStatus::Confirmed->value)->where('is_lead', true)->with(['user', 'contact'])->first();

                if ($lead !== null) {
                    $notices->reportReminder($seminar, $lead);
                    $reminded++;
                }

                $seminar->forceFill(['report_reminded_at' => now()])->save();
            }
        });

        $this->info('Erinnerungen: '.$reminded.', überfällig gemeldet: '.$overdue.'.');

        return self::SUCCESS;
    }
}
