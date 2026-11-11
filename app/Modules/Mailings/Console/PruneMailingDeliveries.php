<?php

namespace App\Modules\Mailings\Console;

use App\Modules\Mailings\Models\Mailing;
use App\Modules\Mailings\Models\MailingAttachment;
use App\Modules\Mailings\Models\MailingDelivery;
use Illuminate\Console\Command;

/**
 * Löscht die Zustelllisten (Adressen und Fehlergründe) abgeschlossener Schreiben nach der Aufbewahrungsfrist
 * (portal.mailings.delivery_retention_days, Standard 90 Tage). Betreff, Text und Zahlen der Schreiben bleiben.
 */
class PruneMailingDeliveries extends Command
{
    protected $signature = 'mailings:prune {--dry-run : Nur zählen, nichts löschen}';

    protected $description = 'Löscht alte Zustelllisten der Schreiben an Gruppen.';

    public function handle(): int
    {
        $days = max(1, (int) config('portal.mailings.delivery_retention_days', 90));
        $cutoff = now()->subDays($days);

        $finished = Mailing::query()
            ->whereIn('status', [Mailing::STATUS_DONE, Mailing::STATUS_CANCELLED, Mailing::STATUS_FAILED])
            ->whereRaw('COALESCE(finished_at, created_at) < ?', [$cutoff])
            ->select('id');

        $query = MailingDelivery::query()->whereIn('mailing_id', $finished);
        $count = (clone $query)->count();

        if (! $this->option('dry-run')) {
            $query->delete();
            // Anhänge werden mit den Zustelllisten gelöscht (Datei und Datensatz)
            MailingAttachment::query()->whereIn('mailing_id', $finished)->get()->each->delete();
        }

        $this->components->info($count.' Zustellungen '.($this->option('dry-run') ? 'würden gelöscht' : 'gelöscht').' (älter als '.$days.' Tage).');

        return self::SUCCESS;
    }
}
