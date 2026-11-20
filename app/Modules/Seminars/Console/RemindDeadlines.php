<?php

namespace App\Modules\Seminars\Console;

use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Services\SeminarNotifications;
use Illuminate\Console\Command;

/** Erinnert Teamende, die sich noch nicht gemeldet haben, drei Tage vor Ende der Bewerbungsfrist (einmal je Seminar). */
class RemindDeadlines extends Command
{
    protected $signature = 'seminars:remind-deadlines {--dry-run : Nur zählen, nichts senden}';

    protected $description = 'Erinnert an die Bewerbungsfrist ausgeschriebener Seminare';

    public function handle(SeminarNotifications $notifications): int
    {
        $due = Seminar::query()->with('partner')->where('status', SeminarStatus::Tendered->value)->whereNull('tender_reminded_at')
            ->whereBetween('application_deadline', [today()->toDateString(), today()->addDays(3)->toDateString()])->get();

        foreach ($due as $seminar) {
            if (! $this->option('dry-run')) {
                $notifications->deadlineNear($seminar);
                $seminar->forceFill(['tender_reminded_at' => now()])->save();
            }
        }

        $this->info('Fristerinnerungen: '.$due->count());

        return self::SUCCESS;
    }
}
