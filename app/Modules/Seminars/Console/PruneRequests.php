<?php

namespace App\Modules\Seminars\Console;

use App\Modules\Contracts\Models\SeminarContract;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Anfragen ohne Vertrag (abgelehnt oder abgesagt) werden ein Jahr nach dem Abschluss gelöscht (docs/87). Seminare mit unterschriebenem Vertrag der Schule, Verträgen der Teamenden,
 * Rechnungen oder Verträgen als Dokument bleiben, denn für sie gelten die gesetzlichen Aufbewahrungsfristen.
 */
class PruneRequests extends Command
{
    protected $signature = 'seminars:prune-requests {--dry-run : Nur zählen, nichts löschen}';

    protected $description = 'Löscht abgelehnte und abgesagte Anfragen ohne Vertrag nach der Aufbewahrungsfrist.';

    public function handle(): int
    {
        $cut = now()->subYears(max(1, (int) config('portal.seminars.retention.request_years', 1)));

        $query = Seminar::query()
            ->whereIn('status', [SeminarStatus::Rejected->value, SeminarStatus::Cancelled->value])
            ->where('updated_at', '<', $cut)
            ->whereNull('school_contract_signed_at')
            ->whereNotIn('id', SeminarContract::query()->select('seminar_id'))
            ->whereDoesntHave('documents', fn ($documents) => $documents->whereIn('kind', ['contract', 'invoice']));

        $count = (clone $query)->count();

        if (! $this->option('dry-run')) {
            $query->each(function (Seminar $seminar): void {
                Storage::disk(SeminarDocument::DISK)->deleteDirectory('seminars/'.$seminar->id);
                $seminar->delete();
            });
        }

        $this->components->info(($this->option('dry-run') ? 'Probelauf: ' : '').$count.' Anfrage(n) ohne Vertrag '.($this->option('dry-run') ? 'würden gelöscht' : 'gelöscht').'.');

        return self::SUCCESS;
    }
}
