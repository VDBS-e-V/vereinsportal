<?php

use App\Modules\DesignLibrary\Support\DesignScaffolder;
use App\Modules\DesignLibrary\Support\Registry;
use Illuminate\Support\Facades\File;

/** Werkzeug für Design-Pakete (design:package, design:component, design:check) in einem Wegwerf-Projekt. */
function dsProject(): string
{
    $root = sys_get_temp_dir().'/vdbs-design-'.uniqid();
    File::ensureDirectoryExists($root.'/resources/css');
    File::ensureDirectoryExists($root.'/app/Modules/DesignLibrary/Packages');
    File::put($root.'/resources/css/app.css', "@import './vdbs/components/content/index.css';\n/* design-packages:imports (Anker) */\n\n/* Rest */\n");

    return $root;
}

function dsScaffolder(string $root, bool $dry = false, bool $force = false): DesignScaffolder
{
    return new DesignScaffolder($root, base_path('stubs/design'), $dry, $force);
}

afterEach(function () {
    foreach (glob(sys_get_temp_dir().'/vdbs-design-*') ?: [] as $dir) {
        File::deleteDirectory($dir);
    }
});

it('turns names into valid slugs and refuses invalid ones', function () {
    expect(DesignScaffolder::slug('Info Box'))->toBe('info-box')
        ->and(DesignScaffolder::slug('tabellenPlus'))->toBe('tabellen-plus')
        ->and(fn () => DesignScaffolder::slug('9abc'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => DesignScaffolder::slug('!!!'))->toThrow(InvalidArgumentException::class);
});

it('creates a package with css entry, manifest, test and the import before the anchor', function () {
    $root = dsProject();
    dsScaffolder($root)->createPackage('Meine Karten', 'Meine Karten', 'Karten für Tests.');

    expect(is_file($root.'/resources/css/vdbs/components/meine-karten/index.css'))->toBeTrue()
        ->and(is_file($root.'/tests/Feature/DesignLibrary/Packages/MeineKartenTest.php'))->toBeTrue()
        ->and(is_dir($root.'/resources/views/components/vdbs/meine-karten'))->toBeTrue();

    $manifest = require $root.'/app/Modules/DesignLibrary/Packages/meine-karten.php';
    expect($manifest)->toBe(['title' => 'Meine Karten', 'description' => 'Karten für Tests.', 'components' => []]);

    $css = file_get_contents($root.'/resources/css/app.css');
    expect($css)->toContain("@import './vdbs/components/meine-karten/index.css';")
        ->and(strpos($css, 'meine-karten'))->toBeLessThan(strpos($css, 'design-packages:imports'));
});

it('refuses to create a package twice and imports only once', function () {
    $root = dsProject();
    dsScaffolder($root)->createPackage('karten');

    expect(fn () => dsScaffolder($root)->createPackage('karten'))->toThrow(RuntimeException::class, 'gibt es schon');

    dsScaffolder($root, false, true)->createPackage('karten');
    expect(substr_count(file_get_contents($root.'/resources/css/app.css'), 'karten/index.css'))->toBe(1);
});

it('creates a component with blade, css, example page, manifest entry and tests', function () {
    $root = dsProject();
    dsScaffolder($root)->createPackage('karten', 'Karten');
    dsScaffolder($root)->createComponent('karten', 'Info Box', 'Info-Box', 'Eine Box mit Hinweis.', ['icon', 'tone-name'], true);

    $blade = file_get_contents($root.'/resources/views/components/vdbs/karten/info-box.blade.php');
    expect($blade)->toContain("'variant' => 'default', 'icon' => null, 'toneName' => null")->toContain('vdbs-info-box')->toContain('Paket "Karten"');

    expect(file_get_contents($root.'/resources/css/vdbs/components/karten/info-box.css'))->toContain('.vdbs-info-box {')->toContain('var(--bg-surface)')
        ->and(file_get_contents($root.'/resources/css/vdbs/components/karten/index.css'))->toContain("@import './info-box.css';")
        ->and(file_get_contents($root.'/resources/views/design-library/entries/karten-info-box.blade.php'))->toContain('<x-vdbs.karten.info-box');

    $test = file_get_contents($root.'/tests/Feature/DesignLibrary/Packages/KartenTest.php');
    expect($test)->toContain('x-vdbs.karten.info-box')->toContain("toContain('vdbs-info-box--default')");

    $manifest = require $root.'/app/Modules/DesignLibrary/Packages/karten.php';
    expect($manifest['components'])->toBe([['name' => 'info-box', 'title' => 'Info-Box', 'description' => 'Eine Box mit Hinweis.', 'status' => 'experimental', 'width' => 'wide']]);

    expect(dsScaffolder($root)->check())->toBe([]);
});

it('creates nothing when a file of the component already exists', function () {
    $root = dsProject();
    dsScaffolder($root)->createPackage('karten');
    File::ensureDirectoryExists($root.'/resources/css/vdbs/components/karten');
    File::put($root.'/resources/css/vdbs/components/karten/box.css', '.vdbs-box { }');

    expect(fn () => dsScaffolder($root)->createComponent('karten', 'box'))->toThrow(RuntimeException::class, 'Es wurde nichts angelegt');
    expect(is_file($root.'/resources/views/components/vdbs/karten/box.blade.php'))->toBeFalse()
        ->and((require $root.'/app/Modules/DesignLibrary/Packages/karten.php')['components'])->toBe([]);
});

it('needs an existing package for a component', function () {
    expect(fn () => dsScaffolder(dsProject())->createComponent('gibt-es-nicht', 'box'))->toThrow(RuntimeException::class, 'gibt es nicht');
});

it('only reports in a dry run', function () {
    $root = dsProject();
    $scaffolder = dsScaffolder($root, true);
    $scaffolder->createPackage('karten');

    expect($scaffolder->log())->not->toBe([])
        ->and(file_exists($root.'/app/Modules/DesignLibrary/Packages/karten.php'))->toBeFalse()
        ->and(file_get_contents($root.'/resources/css/app.css'))->not->toContain('karten');
});

it('finds missing files, color codes and primitive tokens', function () {
    $root = dsProject();
    dsScaffolder($root)->createPackage('karten');
    dsScaffolder($root)->createComponent('karten', 'box');

    File::put($root.'/resources/css/vdbs/components/karten/box.css', ".vdbs-box { color: #ff0000; background: var(--color-gruen); }\n");
    File::delete($root.'/resources/views/design-library/entries/karten-box.blade.php');

    $problems = implode("\n", dsScaffolder($root)->check('karten'));

    expect($problems)->toContain('Farbcode im CSS')->toContain('Primitive Farb-Tokens')->toContain('Beispielseite fehlt');
    expect(dsScaffolder($root)->check('fehlt'))->toBe(['fehlt: Manifest fehlt (app/Modules/DesignLibrary/Packages/fehlt.php).']);
});

it('lists the components of the manifests as library entries', function () {
    $root = dsProject();
    dsScaffolder($root)->createPackage('karten', 'Karten');
    dsScaffolder($root)->createComponent('karten', 'box', 'Box', 'Eine Box.');

    $definitions = Registry::packageDefinitions($root.'/app/Modules/DesignLibrary/Packages');

    expect($definitions)->toHaveCount(1)
        ->and($definitions[0]['slug'])->toBe('karten-box')
        ->and($definitions[0]['title'])->toBe('Box')
        ->and($definitions[0]['styleguide'])->toBe('Paket Karten');
});

it('runs the check command on the real project without problems', function () {
    $this->artisan('design:check')->assertSuccessful();
});

it('explains an unknown package in the command', function () {
    $this->artisan('design:component', ['package' => 'gibt-es-nicht-xyz', 'name' => 'box'])->assertFailed();
});
