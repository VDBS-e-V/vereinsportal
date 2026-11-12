<?php

namespace App\Modules\Seminars\Console;

use App\Modules\Seminars\Services\TeamApplications;
use Illuminate\Console\Command;

class NotifyTeamDeadlines extends Command
{
    protected $signature = 'seminars:team-deadlines';

    protected $description = 'Meldet der Koordination Seminare, deren Stichtag für Interessenbekundungen vorbei ist (einmal je Stichtag).';

    public function handle(TeamApplications $team): int
    {
        $this->info('Gemeldete Seminare: '.$team->notifyDeadlines().'.');

        return self::SUCCESS;
    }
}
