<?php

namespace App\Modules\Access\Console;

use App\Modules\Access\Services\AccountDeletion;
use Illuminate\Console\Command;

class PurgeAccounts extends Command
{
    protected $signature = 'account:purge';

    protected $description = 'Anonymisiert Konten, deren Löschung beantragt wurde und deren Wartefrist abgelaufen ist.';

    public function handle(AccountDeletion $deletion): int
    {
        $result = $deletion->purgeDue();

        $this->components->info("{$result['done']} Konten anonymisiert, {$result['skipped']} übersprungen.");

        return self::SUCCESS;
    }
}
