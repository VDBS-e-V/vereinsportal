<?php

namespace App\Modules\DesignLibrary\Console;

use App\Modules\DesignLibrary\Support\DesignScaffolder;
use Illuminate\Console\Command;
use InvalidArgumentException;
use RuntimeException;

class DesignAdoptCommand extends Command
{
    protected $signature = 'design:adopt {package? : Name eines vorhandenen Pakets (z. B. actions)} {--all : Alle vorhandenen Pakete} {--force : Vorhandenes Manifest neu erzeugen (der Stand der Umgestaltung geht verloren)} {--dry-run : Nur anzeigen}';

    protected $description = 'Nimmt vorhandene Pakete in die neue Struktur auf: schreibt das Manifest aus Komponenten, CSS und Beispielseiten';

    public function handle(): int
    {
        $packages = $this->option('all') ? array_keys(DesignScaffolder::PACKAGE_TITLES) : array_filter([(string) $this->argument('package')]);

        if ($packages === []) {
            $this->error('Bitte ein Paket nennen (z. B. design:adopt actions) oder --all verwenden.');

            return self::FAILURE;
        }

        $failed = false;

        foreach ($packages as $package) {
            $scaffolder = new DesignScaffolder(base_path(), base_path('stubs/design'), (bool) $this->option('dry-run'), (bool) $this->option('force'));

            try {
                $count = $scaffolder->adopt($package);
                $this->info("{$package}: {$count} Komponenten aufgenommen.");
            } catch (InvalidArgumentException|RuntimeException $exception) {
                $this->warn("{$package}: ".$exception->getMessage());
                $failed = true;
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
