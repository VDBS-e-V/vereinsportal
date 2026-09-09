<?php

use App\Support\WebContentLibrary;
use App\Support\WebContentLibraryReference;

it('links important library entries to their design references', function () {
    $library = app(
        WebContentLibrary::class,
    );
    $reference = app(
        WebContentLibraryReference::class,
    )->for(
        $library->find('button'),
    );

    expect($reference['design_url'])
        ->toEndWith('/design/elemente/buttons')
        ->and($reference['code'])
        ->toContain('class="btn"');
});

it('finds related entries by shared tags', function () {
    $library = app(
        WebContentLibrary::class,
    );

    $related = $library->related(
        $library->find('button'),
    );

    expect($related)
        ->not->toBeEmpty();
});

it('documents references code and related items on the detail page', function () {
    $view = file_get_contents(
        resource_path(
            'views/design/pages/bibliothek/show.blade.php',
        ),
    );

    expect($view)
        ->toContain('Code-Vorlage')
        ->toContain('Verwandte Bausteine')
        ->toContain('Design-Referenz öffnen');
});
