<?php

namespace App\Modules\DesignLibrary\Console;

use App\Modules\DesignLibrary\Support\DesignScaffolder;
use Illuminate\Console\Command;
use InvalidArgumentException;

class DesignCheckCommand extends Command
{
    protected $signature = 'design:check {package? : Nur dieses Paket prüfen}';

    protected $description = 'Prüft Design-Pakete: Dateien, Importe, Beispielseiten, Tests und Tokens im CSS';

    public function handle(): int
    {
        $scaffolder = new DesignScaffolder(base_path(), base_path('stubs/design'));

        try {
            $problems = $scaffolder->check($this->argument('package'));
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        foreach ($problems as $problem) {
            $this->warn($problem);
        }

        if ($problems === []) {
            $this->info('Alle Design-Pakete sind in Ordnung.');

            return self::SUCCESS;
        }

        $this->error(count($problems).' Problem(e) gefunden.');

        return self::FAILURE;
    }
}
