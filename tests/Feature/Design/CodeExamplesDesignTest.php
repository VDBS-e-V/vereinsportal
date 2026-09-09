<?php

use Illuminate\Support\Facades\Route;

it('documents copyable code examples', function () {
    expect(Route::has('design.bibliothek.code'))
        ->toBeTrue();

    $component = file_get_contents(
        resource_path(
            'views/components/vdbs/code-example.blade.php',
        ),
    );

    expect($component)
        ->toContain('data-vdbs-code-example')
        ->toContain('data-vdbs-copy-code')
        ->toContain('data-vdbs-code-source')
        ->toContain('aria-live="polite"');
});

it('renders code templates without blade parser conflicts', function () {
    $html = view(
        'design.pages.bibliothek.code',
    )->render();

    expect($html)
        ->toContain('Code-Vorlagen')
        ->toContain('Zur Übersicht')
        ->toContain('Code kopieren')
        ->toContain(
            e('<x-vdbs.icon name="calendar" size="20" />'),
        );
});

it('loads the content library javascript and styles', function () {
    $javascript = file_get_contents(
        resource_path('js/app.js'),
    );
    $css = file_get_contents(
        resource_path('css/app.css'),
    );

    expect($javascript)
        ->toContain("import './content-library';")
        ->and($css)
        ->toContain('./vdbs/components/code-examples.css');
});
