<?php

namespace App\Modules\DesignLibrary\Support;

use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * Legt Design-Pakete und ihre Komponenten an und prüft sie (Befehle design:package, design:component, design:check).
 * Alles, was entsteht: Blade-Komponente, CSS, Beispielseite für die Design-Bibliothek, Manifest-Eintrag, Test. Existierendes wird nie überschrieben
 * (außer mit force). Mit dryRun entsteht nichts, die Aktionen werden nur gemeldet. $root ist das Projekt (zum Testen ein Ordner), $stubs der Vorlagenordner.
 */
final class DesignScaffolder
{
    public const CSS_ANCHOR = '/* design-packages:imports';

    /** @var list<string> */
    private array $log = [];

    public function __construct(private readonly string $root, private readonly string $stubs, private readonly bool $dryRun = false, private readonly bool $force = false) {}

    /** @return list<string> die Aktionen, in der Reihenfolge */
    public function log(): array
    {
        return $this->log;
    }

    public static function slug(string $value): string
    {
        $slug = Str::slug(Str::snake($value, '-'));

        if (! preg_match('/^[a-z][a-z0-9-]*$/', $slug)) {
            throw new InvalidArgumentException("„{$value}“ ist kein gültiger Name. Erlaubt sind Buchstaben, Ziffern und Bindestriche; er beginnt mit einem Buchstaben.");
        }

        return $slug;
    }

    public function createPackage(string $name, ?string $title = null, ?string $description = null): void
    {
        $package = self::slug($name);
        $title = $title !== null && $title !== '' ? $title : Str::headline($package);
        $description = $description !== null && $description !== '' ? $description : 'Design-Paket '.$title.'.';

        if (is_file($this->manifestPath($package)) && ! $this->force) {
            throw new RuntimeException("Das Paket „{$package}“ gibt es schon (Manifest vorhanden). Mit --force neu anlegen.");
        }

        $this->write($this->path('resources/css/vdbs/components/'.$package.'/index.css'), $this->render('package.css.stub', ['__PACKAGE_TITLE__' => $title]));
        $this->write($this->path('tests/Feature/DesignLibrary/Packages/'.Str::studly($package).'Test.php'), $this->render('test.stub', ['__PACKAGE_TITLE__' => $title, '__PACKAGE__' => $package]));
        $this->writeManifest($package, ['title' => $title, 'description' => $description, 'components' => []]);
        $this->addCssImport("@import './vdbs/components/{$package}/index.css';", $this->path('resources/css/app.css'));
        $this->mkdir($this->path('resources/views/components/vdbs/'.$package));
    }

    /**
     * @param  list<string>  $props  zusätzliche Eigenschaften der Komponente
     */
    public function createComponent(string $package, string $name, ?string $title = null, ?string $description = null, array $props = [], bool $wide = false): void
    {
        $package = self::slug($package);
        $name = self::slug($name);
        $manifest = $this->manifest($package);
        $title = $title !== null && $title !== '' ? $title : Str::headline($name);
        $description = $description !== null && $description !== '' ? $description : $title.' (Paket '.$manifest['title'].').';

        foreach ($manifest['components'] as $existing) {
            if ($existing['name'] === $name && ! $this->force) {
                throw new RuntimeException("Die Komponente „{$name}“ gibt es im Paket „{$package}“ schon. Mit --force neu anlegen.");
            }
        }

        $propList = '';

        foreach ($props as $prop) {
            $propList .= ", '".Str::camel(self::slug($prop))."' => null";
        }

        $replace = ['__NAME__' => $name, '__PACKAGE__' => $package, '__TITLE__' => $title, '__PACKAGE_TITLE__' => $manifest['title'], '__PROPS__' => $propList];

        // Alles vorab prüfen, damit nicht halb angelegt wird.
        if (! $this->force) {
            foreach (["resources/views/components/vdbs/{$package}/{$name}.blade.php", "resources/css/vdbs/components/{$package}/{$name}.css", "resources/views/design-library/entries/{$package}-{$name}.blade.php"] as $target) {
                if (is_file($this->path($target))) {
                    throw new RuntimeException("Die Datei gibt es schon: {$target}. Es wurde nichts angelegt. Mit --force überschreiben.");
                }
            }
        }

        $this->write($this->path("resources/views/components/vdbs/{$package}/{$name}.blade.php"), $this->render('component.blade.stub', $replace));
        $this->write($this->path("resources/css/vdbs/components/{$package}/{$name}.css"), $this->render('component.css.stub', $replace));
        $this->write($this->path("resources/views/design-library/entries/{$package}-{$name}.blade.php"), $this->render('entry.blade.stub', $replace));
        $this->addCssImport("@import './{$name}.css';", $this->path("resources/css/vdbs/components/{$package}/index.css"));
        $this->appendTest($package, $name, $replace);

        $manifest['components'] = array_values(array_filter($manifest['components'], fn (array $c): bool => $c['name'] !== $name));
        $manifest['components'][] = ['name' => $name, 'title' => $title, 'description' => $description, 'status' => 'experimental', 'width' => $wide ? 'wide' : 'default'];
        $this->writeManifest($package, $manifest);
    }

    /**
     * Prüft ein Paket (oder alle): Dateien, CSS-Import, Beispielseite, Test, keine Farbcodes im CSS.
     *
     * @return list<string> die Probleme (leer = alles in Ordnung)
     */
    public function check(?string $package = null): array
    {
        $problems = [];
        $packages = $package !== null ? [self::slug($package)] : array_map(fn (string $file): string => basename($file, '.php'), glob($this->path('app/Modules/DesignLibrary/Packages').'/*.php') ?: []);

        foreach ($packages as $pkg) {
            if (! is_file($this->manifestPath($pkg))) {
                $problems[] = "{$pkg}: Manifest fehlt (app/Modules/DesignLibrary/Packages/{$pkg}.php).";

                continue;
            }

            $manifest = $this->manifest($pkg);
            $index = $this->path("resources/css/vdbs/components/{$pkg}/index.css");
            $app = is_file($this->path('resources/css/app.css')) ? (string) file_get_contents($this->path('resources/css/app.css')) : '';

            if (! is_file($index)) {
                $problems[] = "{$pkg}: index.css fehlt.";
            }

            if (! str_contains($app, "./vdbs/components/{$pkg}/index.css")) {
                $problems[] = "{$pkg}: nicht in resources/css/app.css eingebunden.";
            }

            $test = $this->path('tests/Feature/DesignLibrary/Packages/'.Str::studly($pkg).'Test.php');
            $testCode = is_file($test) ? (string) file_get_contents($test) : null;

            if ($testCode === null) {
                $problems[] = "{$pkg}: Testdatei fehlt.";
            }

            foreach ($manifest['components'] as $component) {
                $name = $component['name'];
                $files = [
                    "resources/views/components/vdbs/{$pkg}/{$name}.blade.php" => 'Komponente',
                    "resources/css/vdbs/components/{$pkg}/{$name}.css" => 'CSS',
                    "resources/views/design-library/entries/{$pkg}-{$name}.blade.php" => 'Beispielseite',
                ];

                foreach ($files as $file => $label) {
                    if (! is_file($this->path($file))) {
                        $problems[] = "{$pkg}/{$name}: {$label} fehlt ({$file}).";
                    }
                }

                if (is_file($index) && ! str_contains((string) file_get_contents($index), "./{$name}.css")) {
                    $problems[] = "{$pkg}/{$name}: CSS nicht in {$pkg}/index.css eingebunden.";
                }

                if ($testCode !== null && ! str_contains($testCode, "x-vdbs.{$pkg}.{$name}")) {
                    $problems[] = "{$pkg}/{$name}: kein Test in der Paket-Testdatei.";
                }

                $css = $this->path("resources/css/vdbs/components/{$pkg}/{$name}.css");

                if (is_file($css)) {
                    $code = (string) preg_replace('~/\*.*?\*/~s', '', (string) file_get_contents($css));

                    if (preg_match('/#[0-9a-fA-F]{3,8}\b|\brgba?\(|\bhsla?\(|\bhsl\(/', $code)) {
                        $problems[] = "{$pkg}/{$name}: Farbcode im CSS (nur Tokens aus tokens.css verwenden).";
                    }

                    if (preg_match('/var\(--(color|alpha)-/', $code)) {
                        $problems[] = "{$pkg}/{$name}: Primitive Farb-Tokens im CSS (nur semantische Tokens wie --bg-*, --text-*, --border-*).";
                    }

                    if (! str_contains($code, '.vdbs-'.$name)) {
                        $problems[] = "{$pkg}/{$name}: das CSS enthält die Klasse .vdbs-{$name} nicht.";
                    }
                }
            }
        }

        return $problems;
    }

    /** @return array{title: string, description: string, components: list<array<string, string>>} */
    public function manifest(string $package): array
    {
        if (! is_file($this->manifestPath($package))) {
            throw new RuntimeException("Das Paket „{$package}“ gibt es nicht. Zuerst: php artisan design:package {$package}");
        }

        return require $this->manifestPath($package);
    }

    private function manifestPath(string $package): string
    {
        return $this->path('app/Modules/DesignLibrary/Packages/'.$package.'.php');
    }

    /** @param  array<string, mixed>  $manifest */
    private function writeManifest(string $package, array $manifest): void
    {
        $code = "<?php\n\n/** Manifest des Design-Pakets „{$manifest['title']}“. Wird von design:component ergänzt; Titel, Beschreibung und Status dürfen Sie von Hand ändern. */\nreturn ".$this->export($manifest).";\n";

        $this->write($this->manifestPath($package), $code, true);
    }

    private function export(mixed $value, int $level = 0): string
    {
        if (! is_array($value)) {
            return var_export($value, true);
        }

        if ($value === []) {
            return '[]';
        }

        $indent = str_repeat('    ', $level + 1);
        $lines = [];

        foreach ($value as $key => $item) {
            $lines[] = $indent.(is_int($key) ? '' : var_export($key, true).' => ').$this->export($item, $level + 1).',';
        }

        return "[\n".implode("\n", $lines)."\n".str_repeat('    ', $level).']';
    }

    private function path(string $relative): string
    {
        return rtrim($this->root, '/\\').'/'.$relative;
    }

    /** @param  array<string, string>  $replace */
    private function render(string $stub, array $replace): string
    {
        $file = rtrim($this->stubs, '/\\').'/'.$stub;

        if (! is_file($file)) {
            throw new RuntimeException("Vorlage fehlt: {$file}");
        }

        return strtr((string) file_get_contents($file), $replace);
    }

    private function mkdir(string $directory): void
    {
        if (! is_dir($directory)) {
            $this->log[] = 'Ordner  '.$this->relative($directory);

            if (! $this->dryRun) {
                mkdir($directory, 0777, true);
            }
        }
    }

    /** Schreibt eine Datei; eine vorhandene bleibt unberührt (außer bei force oder $replace für Manifeste). */
    private function write(string $path, string $content, bool $replace = false): void
    {
        $exists = is_file($path);

        if ($exists && ! $replace && ! $this->force) {
            throw new RuntimeException('Die Datei gibt es schon: '.$this->relative($path).'. Mit --force überschreiben.');
        }

        $this->log[] = ($exists ? 'Ersetzt ' : 'Neu     ').$this->relative($path);

        if ($this->dryRun) {
            return;
        }

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, $content);
    }

    /** Fügt eine @import-Zeile ein: im Paket-CSS am Ende, in app.css hinter den Anker. Doppelte Zeilen entstehen nicht. */
    private function addCssImport(string $line, string $file): void
    {
        $current = is_file($file) ? (string) file_get_contents($file) : null;

        if ($current === null) {
            if ($this->dryRun) {
                $this->log[] = 'Ergänzt '.$this->relative($file).': '.$line;

                return;
            }

            throw new RuntimeException('CSS-Datei fehlt: '.$this->relative($file));
        }

        if (str_contains($current, $line)) {
            return;
        }

        if (str_ends_with($file, 'app.css')) {
            $position = strpos($current, self::CSS_ANCHOR);

            if ($position === false) {
                throw new RuntimeException('In resources/css/app.css fehlt der Anker „'.self::CSS_ANCHOR.'“. Bitte die Zeile von Hand ergänzen: '.$line);
            }

            $updated = substr($current, 0, $position).$line."\n".substr($current, $position);
        } else {
            $updated = rtrim($current)."\n".$line."\n";
        }

        $this->log[] = 'Ergänzt '.$this->relative($file).': '.$line;

        if (! $this->dryRun) {
            file_put_contents($file, $updated);
        }
    }

    /** @param  array<string, string>  $replace */
    private function appendTest(string $package, string $name, array $replace): void
    {
        $file = $this->path('tests/Feature/DesignLibrary/Packages/'.Str::studly($package).'Test.php');

        if (! is_file($file)) {
            $this->write($file, $this->render('test.stub', $replace));

            if ($this->dryRun) {
                $this->log[] = 'Ergänzt '.$this->relative($file).': Tests für '.$name;

                return;
            }
        }

        $current = (string) file_get_contents($file);

        if (str_contains($current, "x-vdbs.{$package}.{$name}>")) {
            return;
        }

        $this->log[] = 'Ergänzt '.$this->relative($file).': Tests für '.$name;

        if (! $this->dryRun) {
            file_put_contents($file, rtrim($current)."\n".$this->render('component-test.stub', $replace));
        }
    }

    private function relative(string $path): string
    {
        return ltrim(str_replace([str_replace('\\', '/', rtrim($this->root, '/\\')), '\\'], ['', '/'], $path), '/');
    }
}
