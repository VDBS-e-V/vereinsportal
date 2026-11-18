<?php

namespace App\Modules\Seminars\Console;

use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Löscht abgelehnte und abgesagte Anfragen ohne Vertrag ein Jahr nach der letzten Änderung (samt Dateien).
 * Seminare mit unterschriebenem Schulvertrag, Freigabe oder Zahlungsauftrag bleiben: Verträge und Belege mit Geldbezug folgen den gesetzlichen Fristen.
 */
class PruneRequests extends Command
{
    protected $signature = 'seminars:prune-requests {--dry-run : Nur zählen, nichts löschen}';

    protected $description = 'Löscht alte abgelehnte oder abgesagte Seminaranfragen ohne Vertrag';

    public function handle(): int
    {
        $limit = now()->subYears(max(1, (int) config('portal.seminars.request_years', 1)));

        $seminars = Seminar::query()->whereIn('status', [SeminarStatus::Rejected->value, SeminarStatus::Cancelled->value])->where('updated_at', '<', $limit)
            ->whereNull('school_contract_signed_on')->whereNull('release_basis')->whereDoesntHave('paymentOrders')->get();

        foreach ($seminars as $seminar) {
            if ($this->option('dry-run')) {
                continue;
            }

            foreach ($seminar->documents as $document) {
                Storage::disk(SeminarDocument::DISK)->delete($document->path);
            }

            $seminar->delete();
        }

        $this->info('Gelöschte Anfragen: '.$seminars->count());

        return self::SUCCESS;
    }
}
