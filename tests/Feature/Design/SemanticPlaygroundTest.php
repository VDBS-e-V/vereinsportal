<?php

use App\Support\SemanticExampleBuilder;
use Illuminate\Support\Facades\Route;

it('builds controlled notice status and badge examples', function () {
    $builder = app(
        SemanticExampleBuilder::class,
    );

    $notice = $builder->build(
        'notice',
        'warning',
        'Bitte prüfen.',
    );
    $status = $builder->build(
        'status',
        'success',
        'Aktiv',
    );
    $badge = $builder->build(
        'badge',
        'accent',
        'Neu',
    );

    expect($notice['code'])
        ->toContain('role="alert"')
        ->and($status['code'])
        ->toContain('<x-vdbs.status')
        ->and($badge['code'])
        ->toContain('tone="accent"');
});

it('registers the semantic playground', function () {
    expect(Route::has('design.bibliothek.semantic'))
        ->toBeTrue();
});

it('gives visible and announced feedback after copying code', function () {
    $component = file_get_contents(
        resource_path(
            'views/components/vdbs/code-example.blade.php',
        ),
    );
    $javascript = file_get_contents(
        resource_path(
            'js/content-library.js',
        ),
    );

    expect($component)
        ->toContain('data-vdbs-copy-label')
        ->and($javascript)
        ->toContain("label.textContent = 'Kopiert'")
        ->toContain("status.textContent = 'Code kopiert.'");
});
