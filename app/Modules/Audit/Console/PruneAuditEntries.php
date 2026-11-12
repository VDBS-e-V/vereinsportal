<?php

namespace App\Modules\Audit\Console;

use App\Modules\Audit\Models\AuditEntry;
use Illuminate\Console\Command;

class PruneAuditEntries extends Command
{
    protected $signature = 'audit:prune {--days= : Aufbewahrung in Tagen (Standard: config audit.retention_days)}';

    protected $description = 'Löscht Protokolleinträge, die älter als die Aufbewahrungsfrist sind.';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('audit.retention_days'));

        if ($days < 30) {
            $this->components->error('Die Aufbewahrung muss mindestens 30 Tage betragen.');

            return self::FAILURE;
        }

        // Massenlöschung über den Query Builder: bewusst nur hier, einzelne Einträge sind nicht löschbar.
        $deleted = AuditEntry::query()->where('occurred_at', '<', now()->subDays($days))->delete();

        $this->components->info("{$deleted} Einträge gelöscht (älter als {$days} Tage).");

        return self::SUCCESS;
    }
}
