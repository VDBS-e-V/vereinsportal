<?php

namespace App\Modules\Events\Console;

use App\Modules\Events\Services\EventRegistrations;
use Illuminate\Console\Command;

class RemindEvents extends Command
{
    protected $signature = 'events:remind {--dry-run : Nur zählen, nichts senden}';

    protected $description = 'Erinnert bestätigte Angemeldete einmalig am Tag vor dem ersten anstehenden Termin einer Großveranstaltung.';

    public function handle(EventRegistrations $registrations): int
    {
        $dry = (bool) $this->option('dry-run');

        $this->info(($dry ? '[Trockenlauf] ' : '').'Erinnerungen: '.$registrations->remind($dry).'.');

        return self::SUCCESS;
    }
}
