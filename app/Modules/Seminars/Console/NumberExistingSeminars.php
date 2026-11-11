<?php

namespace App\Modules\Seminars\Console;

use App\Modules\Seminars\Services\SeminarNumbers;
use Illuminate\Console\Command;

/** Vergibt Seminarnummern an Seminare, die noch keine haben. Kann beliebig oft laufen. */
class NumberExistingSeminars extends Command
{
    protected $signature = 'seminars:number-existing';

    protected $description = 'Trägt Seminarnummern bei vorhandenen Seminaren ohne Nummer nach.';

    public function handle(SeminarNumbers $numbers): int
    {
        $count = $numbers->numberExisting();

        $this->components->info($count === 0 ? 'Alle Seminare haben bereits eine Nummer.' : $count.' Seminar(e) nummeriert.');

        return self::SUCCESS;
    }
}
