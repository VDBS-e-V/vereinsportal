<?php

namespace App\Modules\Membership\Console;

use App\Modules\Membership\Services\Memberships;
use Illuminate\Console\Command;

class SyncMemberships extends Command
{
    protected $signature = 'membership:sync';

    protected $description = 'Setzt Mitgliedschaften mit abgelaufenem Austrittsdatum auf beendet und gleicht die Rolle Vereinsmitglied ab.';

    public function handle(Memberships $memberships): int
    {
        $ended = $memberships->syncAll();

        $this->components->info("{$ended} Mitgliedschaften beendet, Rollen abgeglichen.");

        return self::SUCCESS;
    }
}
