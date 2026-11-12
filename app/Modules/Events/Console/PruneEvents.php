<?php

namespace App\Modules\Events\Console;

use App\Modules\Events\Services\EventRegistrations;
use Illuminate\Console\Command;

class PruneEvents extends Command
{
    protected $signature = 'events:prune {--dry-run : Nur zählen, nichts löschen}';

    protected $description = 'Löscht unbestätigte Anmeldungen nach Fristablauf und Anmeldungen alter Veranstaltungen nach der Aufbewahrungszeit.';

    public function handle(EventRegistrations $registrations): int
    {
        $dry = (bool) $this->option('dry-run');
        $counts = $registrations->prune($dry);

        $this->info(($dry ? '[Trockenlauf] ' : '')."Unbestätigte Anmeldungen: {$counts['pending']}, alte Anmeldungen: {$counts['old']}.");

        return self::SUCCESS;
    }
}
