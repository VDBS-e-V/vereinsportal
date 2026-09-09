<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class MakeVdbsComponentCommand extends Command
{
    protected $signature =
        'vdbs:make-component
        {name : Name der neuen Komponente}
        {--dry-run : Nur geplante Dateien anzeigen}';

    protected $description =
        'Erzeugt das Grundgerüst für eine VDBS-Komponente.';

    public function handle(): int
    {
        $name = $this->normalizeName(
            (string) $this->argument('name'),
        );

        $files = $this->files($name);

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
            "VDBS-Komponente [{$name}] wurde angelegt.",
        );

        $this->components->warn(
            'Registry-Eintrag in config/web_content_library.php ergänzen.',
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
                'Der Komponentenname ist ungültig.',
            );
        }

        return $name;
    }

    /**
     * @return array<string, string>
     */
    private function files(string $name): array
    {
        $class = Str::studly($name);

        return [
            "resources/views/components/vdbs/{$name}.blade.php" => "@props([])\n\n<div {{ \$attributes->class(['{$name}', 'vdbs-{$name}']) }}>\n    {{ \$slot }}\n</div>\n",

            "resources/css/vdbs/components/{$name}.css" => "/* VDBS Portal — {$name} */\n\n.{$name},\n.vdbs-{$name} {\n    display: block;\n}\n",

            "resources/views/design/pages/elemente/{$name}.blade.php" => "@extends('design.layout')\n\n@section('title', '{$class}')\n\n@section('content')\n    <div class=\"stack stack--lg\">\n        <header class=\"page-title\">\n            <p class=\"page-title__kicker\">Elemente</p>\n            <h1 class=\"page-title__title\">{$class}</h1>\n        </header>\n\n        <x-vdbs.{$name}>\n            Beispiel\n        </x-vdbs.{$name}>\n    </div>\n@endsection\n",

            "tests/Feature/Design/{$class}DesignTest.php" => "<?php\n\nit('documents the {$name} component', function () {\n    \$component = file_get_contents(\n        resource_path('views/components/vdbs/{$name}.blade.php'),\n    );\n\n    expect(\$component)\n        ->toContain('vdbs-{$name}');\n});\n",
        ];
    }
}
