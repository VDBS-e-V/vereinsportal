<?php

namespace App\Modules\Seminars\Console;

use App\Modules\Seminars\Models\SeminarDocument;
use App\Modules\Seminars\Services\SeminarFolder;
use Illuminate\Console\Command;

/** Löscht Seminardokumente nach Ablauf der Aufbewahrungsfrist (Datei und Datensatz) und räumt liegen gebliebene Seminarmappen auf. Nennt nur Zahlen. */
class PruneDocuments extends Command
{
    protected $signature = 'seminars:prune-documents {--dry-run : Nur zählen, nichts löschen}';

    protected $description = 'Löscht Seminardokumente nach Ablauf der Aufbewahrungsfrist.';

    public function handle(SeminarFolder $folder): int
    {
        $due = SeminarDocument::query()->whereNotNull('retention_until')->where('retention_until', '<', now()->startOfDay());
        $count = (clone $due)->count();

        if (! $this->option('dry-run')) {
            $due->get()->each->delete();
            $folder->cleanTemp();
        }

        $this->components->info(($this->option('dry-run') ? 'Probelauf: ' : '').$count.' Dokument(e) nach Ablauf der Aufbewahrungsfrist '.($this->option('dry-run') ? 'würden gelöscht.' : 'gelöscht.'));

        return self::SUCCESS;
    }
}
