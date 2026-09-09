<?php

use App\Support\WebContentLibrary;
use Illuminate\Support\Facades\Route;

it('registers the web content library in the design system', function () {
    expect(Route::has('design.bibliothek'))
        ->toBeTrue();

    $source = file_get_contents(
        resource_path(
            'views/design/pages/bibliothek/index.blade.php',
        ),
    );

    expect($source)
        ->toContain('Web Content Bibliothek')
        ->toContain('Bibliothekseinträge')
        ->toContain('design.bibliothek');
});

it('searches the library by text category and status', function () {
    $library = app(WebContentLibrary::class);

    expect($library->find('button'))
        ->not->toBeNull()
        ->and(
            $library->search(
                query: 'Fehler',
            )->pluck('id')->all()
        )
        ->toContain('validation-summary')
        ->and(
            $library->search(
                category: 'elements',
                status: 'stable',
            )->pluck('id')->all()
        )
        ->toContain('button', 'icon', 'notice');
});

it('defines a lifecycle for library entries', function () {
    $statuses = app(
        WebContentLibrary::class,
    )->statuses();

    expect(array_keys($statuses))
        ->toBe([
            'experimental',
            'beta',
            'stable',
            'deprecated',
        ]);
});
