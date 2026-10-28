<?php

/**
 * Schutz für das Tokensystem (resources/css/vdbs/tokens.css): Farbcodes nur dort, Komponenten nutzen nur
 * Semantik, der Dark Mode steht in beiden Blöcken identisch da und jede verwendete Variable ist definiert.
 */

function vdbsCssFiles(): array
{
    $root = resource_path('css/vdbs');
    $files = [];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
        $path = str_replace('\\', '/', $file->getPathname());

        if (str_ends_with($path, '.css') && ! str_contains($path, '/_legacy/')) {
            $files[$path] = (string) preg_replace('~/\*.*?\*/~s', '', file_get_contents($path));
        }
    }

    return $files;
}

it('keeps color codes in tokens.css only', function () {
    foreach (vdbsCssFiles() as $path => $css) {
        if (str_ends_with($path, '/vdbs/tokens.css')) {
            continue;
        }

        expect(preg_match('/#[0-9a-fA-F]{3,8}\b|\brgba?\(|\bhsla?\(/', $css))->toBe(0, "Farbcode in {$path}");
    }
});

it('lets components use semantic tokens only', function () {
    foreach (vdbsCssFiles() as $path => $css) {
        if (str_ends_with($path, '/vdbs/tokens.css')) {
            continue;
        }

        expect(preg_match('/var\(--(color|alpha)-/', $css))->toBe(0, "Primitive in {$path}");
    }
});

it('defines every referenced custom property', function () {
    $files = vdbsCssFiles();
    $all = implode("\n", $files);

    preg_match_all('/(--[a-z0-9-]+)\s*:/i', $all, $defined);
    preg_match_all('/var\((--[a-z0-9-]+)/i', $all, $used);

    // Tailwind-Theme-Variablen und Werte, die erst die Blade-Komponente per style-Attribut setzt
    $runtime = ['--tw-', '--code-length'];
    $missing = collect(array_unique($used[1]))
        ->reject(fn (string $name): bool => in_array($name, $defined[1], true) || collect($runtime)->contains(fn ($p) => str_starts_with($name, $p)))
        ->values()
        ->all();

    expect($missing)->toBe([]);
});

it('keeps the two dark mode blocks identical and semantic', function () {
    $css = vdbsCssFiles()[str_replace('\\', '/', resource_path('css/vdbs/tokens.css'))];

    preg_match('/:root\[data-theme="dark"\]\s*\{([^}]*)\}/', $css, $attribute);
    preg_match('/:root:not\(\[data-theme="light"\]\)\s*\{([^}]*)\}/', $css, $media);

    $normalize = fn (string $block): array => collect(preg_split('/;\s*/', trim($block)))->filter()->map(fn ($d) => preg_replace('/\s+/', ' ', trim($d)))->values()->all();

    expect($attribute)->not->toBeEmpty()
        ->and($media)->not->toBeEmpty()
        ->and($normalize($attribute[1]))->toBe($normalize($media[1]))
        ->and($attribute[1])->not->toMatch('/--(color|alpha)-[a-z0-9-]+\s*:/');
});

it('uses the dark green with white text as the action color', function () {
    $css = file_get_contents(resource_path('css/vdbs/tokens.css'));

    expect($css)
        ->toContain('--bg-action: var(--color-gruen-dunkel)')
        ->toContain('--text-on-action: var(--color-weiss)');
});
