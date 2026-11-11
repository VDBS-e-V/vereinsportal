<?php

namespace App\Modules\System\Console;

use App\Modules\System\Services\PortalTick;
use Illuminate\Console\Command;

/** Ein Takt für Cron ohne Dauerprozess: Planer und Warteschlange. Gleicher Code wie die Adresse /cron/{key}. */
class PortalTickCommand extends Command
{
    protected $signature = 'portal:tick';

    protected $description = 'Führt den Planer aus und arbeitet danach wartende Aufträge ab (für Cron auf Webhosting).';

    public function handle(PortalTick $tick): int
    {
        $result = $tick->run('console');

        $this->components->info(match ($result['status']) {
            'busy' => 'Es läuft bereits ein Takt.',
            'error' => 'Der Takt ist mit einem Fehler beendet worden (siehe Fehlerprotokoll).',
            default => 'Takt beendet: '.$result['jobs'].' Aufträge in '.$result['ms'].' ms.',
        });

        return $result['status'] === 'error' ? self::FAILURE : self::SUCCESS;
    }
}
