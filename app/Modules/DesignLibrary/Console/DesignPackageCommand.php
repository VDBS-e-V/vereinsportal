<?php

namespace App\Modules\DesignLibrary\Console;

use App\Modules\DesignLibrary\Support\DesignScaffolder;
use Illuminate\Console\Command;
use InvalidArgumentException;
use RuntimeException;

class DesignPackageCommand extends Command
{
    protected $signature = 'design:package {name : Name des Pakets, z. B. tabellen-plus} {--title= : Anzeigename} {--description= : Kurze Beschreibung} {--force : Vorhandenes überschreiben} {--dry-run : Nur anzeigen, nichts anlegen}';

    protected $description = 'Legt ein Design-Paket an: Ordner, CSS-Einstieg, Manifest und Test-Gerüst';

    public function handle(): int
    {
        $scaffolder = new DesignScaffolder(base_path(), base_path('stubs/design'), (bool) $this->option('dry-run'), (bool) $this->option('force'));

        try {
            $scaffolder->createPackage((string) $this->argument('name'), $this->option('title'), $this->option('description'));
        } catch (InvalidArgumentException|RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        foreach ($scaffolder->log() as $line) {
            $this->line($line);
        }

        $this->info($this->option('dry-run') ? 'Trockenlauf: es wurde nichts angelegt.' : 'Paket angelegt. Weiter mit: php artisan design:component '.DesignScaffolder::slug((string) $this->argument('name')).' <name>');

        return self::SUCCESS;
    }
}
