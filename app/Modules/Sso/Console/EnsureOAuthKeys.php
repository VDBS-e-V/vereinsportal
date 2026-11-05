<?php

namespace App\Modules\Sso\Console;

use App\Modules\Sso\Services\OAuthKeys;
use Illuminate\Console\Command;
use Throwable;

/** Erzeugt die Anmeldeschlüssel, wenn sie noch fehlen (wiederholbar, ändert vorhandene Schlüssel nie). */
class EnsureOAuthKeys extends Command
{
    protected $signature = 'sso:ensure-keys';

    protected $description = 'Erzeugt die Schlüssel für die Anmeldung anderer Systeme über das Portal, falls sie fehlen.';

    public function handle(OAuthKeys $keys): int
    {
        try {
            $created = $keys->ensure();
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info($created ? 'Anmeldeschlüssel wurden erzeugt.' : 'Anmeldeschlüssel sind vorhanden.');

        return self::SUCCESS;
    }
}
