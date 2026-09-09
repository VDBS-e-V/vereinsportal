<?php

use App\Support\WebContentLibrary;
use Illuminate\Support\Facades\Route;

it('inventories every current vdbs blade component and component stylesheet', function () {
    $library = app(
        WebContentLibrary::class,
    );

    $coverage = $library->coverage();

    expect($coverage['missing'])
        ->toBe([])
        ->and($coverage['registered'])
        ->toBe($coverage['discovered'])
        ->and($coverage['discovered'])
        ->toBeGreaterThan(40);
});

it('gives every library entry enough metadata for independent maintenance', function () {
    $items = app(
        WebContentLibrary::class,
    )->all();

    expect($items->count())
        ->toBeGreaterThanOrEqual(40);

    foreach ($items as $item) {
        expect($item)
            ->toHaveKeys([
                'id',
                'name',
                'category',
                'status',
                'description',
                'source',
                'files',
                'tags',
                'usage',
                'avoid',
                'accessibility',
            ])
            ->and($item['files'])
            ->not->toBeEmpty()
            ->and($item['tags'])
            ->not->toBeEmpty()
            ->and($item['usage'])
            ->not->toBeEmpty()
            ->and($item['accessibility'])
            ->not->toBeEmpty();
    }
});

it('registers library detail and contributor routes', function () {
    expect(Route::has('design.bibliothek.show'))
        ->toBeTrue()
        ->and(Route::has('design.bibliothek.contribute'))
        ->toBeTrue();
});

it('searches usage accessibility and source metadata', function () {
    $library = app(
        WebContentLibrary::class,
    );

    expect(
        $library->search('WCAG')
            ->pluck('id')
            ->all()
    )->toContain('accessibility')
        ->and(
            $library->search('ButtonExampleBuilder')
                ->pluck('id')
                ->all()
        )
        ->toContain('button-playground');
});
