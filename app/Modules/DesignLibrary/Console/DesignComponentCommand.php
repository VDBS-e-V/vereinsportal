<?php

namespace App\Modules\DesignLibrary\Console;

use App\Modules\DesignLibrary\Support\DesignScaffolder;
use Illuminate\Console\Command;
use InvalidArgumentException;
use RuntimeException;

class DesignComponentCommand extends Command
{
    protected $signature = 'design:component {package : Name des Pakets} {name : Name der Komponente, z. B. info-box} {--title= : Anzeigename} {--description= : Kurze Beschreibung} {--props= : Zusätzliche Eigenschaften, mit Komma getrennt} {--wide : Beispielseite in der breiten Ansicht} {--force : Vorhandenes überschreiben} {--dry-run : Nur anzeigen, nichts anlegen}';

    protected $description = 'Legt eine Komponente an: Blade, CSS, Beispielseite, Manifest-Eintrag und Test';

    public function handle(): int
    {
        $scaffolder = new DesignScaffolder(base_path(), base_path('stubs/design'), (bool) $this->option('dry-run'), (bool) $this->option('force'));
        $props = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('props')))));

        try {
            $scaffolder->createComponent((string) $this->argument('package'), (string) $this->argument('name'), $this->option('title'), $this->option('description'), $props, (bool) $this->option('wide'));
        } catch (InvalidArgumentException|RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        foreach ($scaffolder->log() as $line) {
            $this->line($line);
        }

        $slug = DesignScaffolder::slug((string) $this->argument('package')).'-'.DesignScaffolder::slug((string) $this->argument('name'));
        $this->info($this->option('dry-run') ? 'Trockenlauf: es wurde nichts angelegt.' : 'Komponente angelegt. Beispielseite: '.url('/design/'.$slug).' (nach npm run build)');

        return self::SUCCESS;
    }
}
