<?php

namespace App\Modules\Feedback\Console;

use App\Modules\Feedback\Services\FeedbackReminders;
use Illuminate\Console\Command;

/** Erinnert Lehrkräfte und Teamende einmalig an die Rückmeldung. Nennt nur Zahlen. */
class RemindFeedback extends Command
{
    protected $signature = 'feedback:remind {--dry-run : Nur zählen, nichts senden}';

    protected $description = 'Erinnert Lehrkräfte und Teamende an die Rückmeldung zum Seminar (einmal je Link).';

    public function handle(FeedbackReminders $reminders): int
    {
        $result = $reminders->run((bool) $this->option('dry-run'));

        $this->components->info(($this->option('dry-run') ? 'Probelauf: ' : '').$result['links'].' Link(s), '.$result['mails'].' Erinnerung(en).');

        return self::SUCCESS;
    }
}
