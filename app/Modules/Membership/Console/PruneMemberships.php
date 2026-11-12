<?php

namespace App\Modules\Membership\Console;

use App\Modules\Membership\Services\MembershipRetention;
use Illuminate\Console\Command;

class PruneMemberships extends Command
{
    protected $signature = 'membership:prune {--dry-run : Nur zählen, nichts löschen}';

    protected $description = 'Löscht unbestätigte Online-Anträge, abgelehnte und (falls eine Frist gesetzt ist) beendete Mitgliedschaften nach den Aufbewahrungsfristen.';

    public function handle(MembershipRetention $retention): int
    {
        $dry = (bool) $this->option('dry-run');
        $counts = $retention->prune($dry);

        $this->info(($dry ? '[Trockenlauf] ' : '')."Unbestätigte Anträge: {$counts['unverified']}, abgelehnte: {$counts['rejected']}, beendete: {$counts['ended']}.");

        return self::SUCCESS;
    }
}
