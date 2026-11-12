<?php

namespace App\Modules\Letters\Console;

use App\Modules\Letters\Services\LetterDefaultsLoader;
use Illuminate\Console\Command;

/** Lädt die mitgelieferten Beispielvorlagen für Briefe. Jede nur einmal; ändert und überschreibt nichts. */
class LoadLetterDefaults extends Command
{
    protected $signature = 'letters:load-defaults {--restore : Fehlende Beispiele auch wieder anlegen, wenn sie schon einmal geladen waren}';

    protected $description = 'Legt die mitgelieferten Beispielvorlagen für Briefe an (überschreibt nichts).';

    public function handle(LetterDefaultsLoader $loader): int
    {
        $result = $loader->load((bool) $this->option('restore'));
        $this->info($result['created'].' Beispielvorlage(n) neu angelegt, '.$result['skipped'].' übersprungen.');

        return self::SUCCESS;
    }
}
