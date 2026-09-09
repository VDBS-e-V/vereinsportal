<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class MakeVdbsPatternCommand extends Command
{
    protected $signature =
        'vdbs:make-pattern
        {name : Name des neuen Musters}
        {--dry-run : Nur geplante Dateien anzeigen}';

    protected $description =
        'Erzeugt Dokumentation und Testgerüst für ein VDBS-Muster.';

    public function handle(): int
    {
        $name = $this->normalizeName(
            (string) $this->argument('name'),
        );

        $class = Str::studly($name);

        $files = [
            "resources/views/design/pages/muster/{$name}.blade.php" => "@extends('design.layout')\n\n@section('title', '{$class}')\n\n@section('content')\n    <div class=\"stack stack--lg\">\n        <header class=\"page-title\">\n            <p class=\"page-title__kicker\">Muster</p>\n            <h1 class=\"page-title__title\">{$class}</h1>\n            <p class=\"page-title__lead\">Verwendung, Grenzen und Codebeispiele dokumentieren.</p>\n        </header>\n    </div>\n@endsection\n",

            "tests/Feature/Design/{$class}PatternDesignTest.php" => "<?php\n\nit('documents the {$name} pattern', function () {\n    \$page = file_get_contents(\n        resource_path('views/design/pages/muster/{$name}.blade.php'),\n    );\n\n    expect(\$page)\n        ->toContain('Muster')\n        ->toContain('{$class}');\n});\n",
        ];

        if ((bool) $this->option('dry-run')) {
            $this->components->info(
                'Geplante Dateien:',
            );

            foreach (array_keys($files) as $path) {
                $this->line($path);
            }

            return self::SUCCESS;
        }

        foreach ($files as $path => $content) {
            $absolutePath = base_path($path);

            if (File::exists($absolutePath)) {
                $this->components->error(
                    "Datei existiert bereits: {$path}",
                );

                return self::FAILURE;
            }

            File::ensureDirectoryExists(
                dirname($absolutePath),
            );

            File::put(
                $absolutePath,
                $content,
            );
        }

        $this->components->info(
            "VDBS-Muster [{$name}] wurde angelegt.",
        );

        $this->components->warn(
            'Route und Registry-Eintrag ergänzen.',
        );

        return self::SUCCESS;
    }

    public function normalizeName(string $name): string
    {
        $name = Str::of($name)
            ->trim()
            ->kebab()
            ->toString();

        if (
            $name === ''
            || preg_match(
                '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                $name,
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                'Der Mustername ist ungültig.',
            );
        }

        return $name;
    }
}
