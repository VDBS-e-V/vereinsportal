<?php

namespace App\Modules\Tasks\Console;

use App\Modules\Tasks\Services\TaskNotifier;
use Illuminate\Console\Command;

/** Schickt Fälligkeits-Erinnerungen an Personen, die sie eingeschaltet haben (einmal je Aufgabe). Nennt nur Zahlen. */
class RemindTasks extends Command
{
    protected $signature = 'tasks:remind {--dry-run : Nur zählen, nichts senden}';

    protected $description = 'Erinnert Personen, die es eingeschaltet haben, an bald fällige und überfällige Aufgaben.';

    public function handle(TaskNotifier $notifier): int
    {
        $result = $notifier->remind((bool) $this->option('dry-run'));

        $this->info(($this->option('dry-run') ? 'Probelauf: ' : '').$result['mails'].' Erinnerung(en) für '.$result['tasks'].' Aufgabe(n).');

        return self::SUCCESS;
    }
}
