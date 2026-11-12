<?php

namespace App\Modules\Forms\Console;

use App\Modules\Forms\Models\FormApplication;
use App\Modules\Forms\Services\FormFiles;
use Illuminate\Console\Command;

/** Aufbewahrung der Anträge: IBAN 30 Tage nach Abschluss löschen, alte Anträge nach der Frist samt Belegen entfernen, ruhende Entwürfe nach 90 Tagen. Nennt nur Zahlen. */
class PruneForms extends Command
{
    protected $signature = 'forms:prune {--dry-run : Nur zählen, nichts löschen}';

    protected $description = 'Löscht IBAN-Daten, abgelaufene Anträge und alte Entwürfe.';

    public function handle(FormFiles $files): int
    {
        $dry = (bool) $this->option('dry-run');

        $ibans = FormApplication::query()->whereNotNull('iban_encrypted')->where(function ($query): void {
            $query->where(fn ($q) => $q->where('status', 'paid')->where('paid_at', '<', now()->subDays(30)))
                ->orWhere(fn ($q) => $q->whereIn('status', ['rejected', 'closed'])->where('updated_at', '<', now()->subDays(30)));
        });
        $ibanCount = (clone $ibans)->count();

        $expired = FormApplication::query()->whereNotNull('retention_until')->where('retention_until', '<', now()->startOfDay());
        $expiredCount = (clone $expired)->count();

        $drafts = FormApplication::query()->where('status', 'draft')->where('updated_at', '<', now()->subDays(90));
        $draftCount = (clone $drafts)->count();

        if (! $dry) {
            $ibans->get()->each(fn (FormApplication $application) => $application->forceFill(['iban_encrypted' => null, 'account_holder' => null])->save());

            foreach ([$expired, $drafts] as $query) {
                $query->get()->each(function (FormApplication $application) use ($files): void {
                    $files->removeAll($application);
                    $application->delete();
                });
            }
        }

        $this->components->info(($dry ? 'Probelauf: ' : '').$ibanCount.' Kontoangabe(n), '.$expiredCount.' abgelaufene(r) Antrag/Anträge und '.$draftCount.' alte(r) Entwurf/Entwürfe '.($dry ? 'würden gelöscht.' : 'gelöscht.'));

        return self::SUCCESS;
    }
}
