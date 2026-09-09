<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

final class MakeDesignPageCommand extends Command
{
    protected $signature = 'make:design-page
        {name : Page slug, e.g. buttons or forms/inputs}
        {--title= : Visible page title}
        {--force : Overwrite existing page and route files}';

    protected $description =
        'Create a VDBS design reference page and its route';

    public function __construct(
        private readonly Filesystem $files,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $name = $this->normalizeName(
            (string) $this->argument('name')
        );

        if ($name === null || $name === 'index') {
            $this->components->error(
                'Bitte einen gültigen Seitennamen verwenden. "index" ist reserviert.'
            );

            return self::FAILURE;
        }

        $segments = explode('/', $name);
        $routeName = implode('.', $segments);
        $viewName = 'design.pages.'.implode('.', $segments);

        $title = trim(
            (string) $this->option('title')
        );

        if ($title === '') {
            $title = Str::headline(
                $segments[array_key_last($segments)]
            );
        }

        $viewPath = resource_path(
            'views/design/pages/'.$name.'.blade.php'
        );

        $routePath = base_path(
            'routes/design/pages/'.$name.'.php'
        );

        $force = (bool) $this->option('force');

        if (
            ! $force
            && (
                $this->files->exists($viewPath)
                || $this->files->exists($routePath)
            )
        ) {
            $this->components->error(
                'Die Designseite oder ihre Route existiert bereits. Nutze --force zum Überschreiben.'
            );

            return self::FAILURE;
        }

        $this->ensureDirectory(
            dirname($viewPath)
        );

        $this->ensureDirectory(
            dirname($routePath)
        );

        $this->files->put(
            $viewPath,
            $this->viewStub(
                title: $title,
            ),
        );

        $this->files->put(
            $routePath,
            $this->routeStub(
                uri: '/'.$name,
                viewName: $viewName,
                routeName: $routeName,
                title: $title,
            ),
        );

        if (app()->routesAreCached()) {
            $this->callSilent('route:clear');
        }

        $this->components->info(
            "Designseite '{$title}' wurde erstellt."
        );

        $this->line(
            'View: '.Str::after($viewPath, base_path().DIRECTORY_SEPARATOR)
        );

        $this->line(
            'Route: /design/'.$name.' (design.'.$routeName.')'
        );

        return self::SUCCESS;
    }

    private function normalizeName(
        string $name,
    ): ?string {
        $name = trim(
            str_replace(
                [
                    '\\',
                    '.',
                ],
                '/',
                $name,
            ),
            '/',
        );

        if ($name === '') {
            return null;
        }

        $segments = array_values(
            array_filter(
                explode('/', $name),
                fn (string $segment): bool => trim($segment) !== '',
            )
        );

        if ($segments === []) {
            return null;
        }

        $segments = array_map(
            fn (string $segment): string => Str::slug($segment),
            $segments,
        );

        if (
            in_array('', $segments, true)
            || in_array('index', $segments, true)
        ) {
            return null;
        }

        return implode('/', $segments);
    }

    private function ensureDirectory(
        string $directory,
    ): void {
        if ($this->files->isDirectory($directory)) {
            return;
        }

        $this->files->makeDirectory(
            $directory,
            0755,
            true,
        );
    }

    private function viewStub(
        string $title,
    ): string {
        $bladeTitle = var_export(
            $title,
            true,
        );

        $htmlTitle = e($title);

        return <<<BLADE
@extends('design.layout')

@section('title', {$bladeTitle})

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Designseite</p>
            <h1 class="page-title__title">{$htmlTitle}</h1>
            <p class="page-title__lead">
                Beschreibe hier Zweck, Varianten und Einsatzregeln dieser
                Vorlage oder dieses Elements.
            </p>
        </header>

        <section class="stack">
            <h2>Beispiel</h2>

            <div class="design-example">
                <!-- Element oder Vorlage hier aufbauen. -->
            </div>
        </section>

        <section class="section stack">
            <h2>Regeln</h2>

            <ul>
                <li>Wann wird das Element eingesetzt?</li>
                <li>Welche Varianten sind erlaubt?</li>
                <li>Welche Tokens und Abstände werden verwendet?</li>
                <li>Welche Accessibility-Anforderungen gelten?</li>
            </ul>
        </section>
    </div>
@endsection
BLADE;
    }

    private function routeStub(
        string $uri,
        string $viewName,
        string $routeName,
        string $title,
    ): string {
        $uri = var_export($uri, true);
        $viewName = var_export($viewName, true);
        $routeName = var_export($routeName, true);
        $title = var_export($title, true);

        return <<<PHP
<?php

use Illuminate\\Support\\Facades\\Route;

Route::view(
    {$uri},
    {$viewName},
)
    ->name({$routeName})
    ->defaults(
        'design_title',
        {$title},
    );
PHP;
    }
}
