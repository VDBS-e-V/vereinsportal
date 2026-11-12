<?php

namespace App\Modules\Mailings\Console;

use App\Modules\Mailings\Services\MailDefaultsLoader;
use Illuminate\Console\Command;

/** Lädt den Grundbestand an Mailvorlagen und Textbausteinen. Ergänzt nur fehlende Einträge, überschreibt nie. */
class LoadMailDefaults extends Command
{
    protected $signature = 'mail:load-defaults';

    protected $description = 'Legt fehlende Mailvorlagen und Textbausteine des Grundbestands an (überschreibt nichts).';

    public function handle(MailDefaultsLoader $loader): int
    {
        $result = $loader->load();
        $this->components->info($result['templates'].' Vorlage(n) und '.$result['snippets'].' Baustein(e) neu angelegt.');

        return self::SUCCESS;
    }
}
