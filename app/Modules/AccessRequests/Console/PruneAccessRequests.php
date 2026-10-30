<?php

namespace App\Modules\AccessRequests\Console;

use App\Modules\AccessRequests\Services\AccessRequests;
use Illuminate\Console\Command;

class PruneAccessRequests extends Command
{
    protected $signature = 'access-requests:prune';

    protected $description = 'Löscht entschiedene und liegengebliebene Zugangsanträge nach der Aufbewahrungsfrist';

    public function handle(AccessRequests $requests): int
    {
        $counts = $requests->prune();

        $this->info("Entschiedene Anträge: {$counts['decided']}, offene: {$counts['open']}.");

        return self::SUCCESS;
    }
}
