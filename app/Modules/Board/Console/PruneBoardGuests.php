<?php

namespace App\Modules\Board\Console;

use App\Modules\Board\Models\BoardMeetingGuest;
use Illuminate\Console\Command;

/**
 * Löscht die Angaben externer Gäste (Name, E-Mail-Adresse, Organisation) eine festgelegte Zeit nach dem Ende der Sitzung
 * (portal.board.guest_retention_days, Standard 30 Tage). Sitzung, Protokoll und Anhänge bleiben als Vereinsunterlagen erhalten. Nennt nur Zahlen.
 */
class PruneBoardGuests extends Command
{
    protected $signature = 'board:prune-guests {--dry-run : Nur zählen, nichts löschen}';

    protected $description = 'Löscht die Angaben externer Gäste von Vorstandssitzungen nach Ablauf der Aufbewahrungsfrist.';

    public function handle(): int
    {
        $days = max(1, (int) config('portal.board.guest_retention_days', 30));
        $query = BoardMeetingGuest::query()->whereHas('meeting', fn ($meeting) => $meeting->where('ends_at', '<', now()->subDays($days)));
        $count = (clone $query)->count();

        if (! $this->option('dry-run')) {
            $query->delete();
        }

        $this->info(($this->option('dry-run') ? 'Probelauf: ' : '').$count.' Gast/Gäste '.($this->option('dry-run') ? 'würden gelöscht.' : 'gelöscht.'));

        return self::SUCCESS;
    }
}
