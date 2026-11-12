<?php

namespace App\Modules\Contracts\Console;

use App\Modules\Contracts\Models\SeminarContract;
use Illuminate\Console\Command;

/** Löscht Verträge nach Ablauf der Aufbewahrung (wie die Seminardokumente, Standard 10 Jahre). Nennt nur Zahlen. */
class PruneContracts extends Command
{
    protected $signature = 'contracts:prune {--dry-run : Nur zählen, nichts löschen}';

    protected $description = 'Löscht Verträge nach Ablauf der Aufbewahrungsfrist.';

    public function handle(): int
    {
        $query = SeminarContract::query()->whereNotNull('retention_until')->where('retention_until', '<', today());
        $count = (clone $query)->count();

        if (! $this->option('dry-run')) {
            $query->delete();
        }

        $this->components->info(($this->option('dry-run') ? 'Probelauf: ' : '').$count.' Vertrag/Verträge '.($this->option('dry-run') ? 'würden gelöscht.' : 'gelöscht.'));

        return self::SUCCESS;
    }
}
