<?php

use App\Console\Commands\MakeVdbsComponentCommand;
use App\Console\Commands\MakeVdbsPatternCommand;
use Illuminate\Support\Facades\Artisan;

it('registers the vdbs library commands', function () {
    $commands = Artisan::all();

    expect(array_keys($commands))
        ->toContain(
            'vdbs:make-component',
            'vdbs:make-pattern',
            'vdbs:library-check',
        );
});

it('supports dry runs for component and pattern generators', function () {
    $componentExit = Artisan::call(
        'vdbs:make-component',
        [
            'name' => 'content-highlight',
            '--dry-run' => true,
        ],
    );

    expect($componentExit)
        ->toBe(0)
        ->and(Artisan::output())
        ->toContain(
            'resources/views/components/vdbs/content-highlight.blade.php',
        );

    $patternExit = Artisan::call(
        'vdbs:make-pattern',
        [
            'name' => 'resource-teaser',
            '--dry-run' => true,
        ],
    );

    expect($patternExit)
        ->toBe(0)
        ->and(Artisan::output())
        ->toContain(
            'resources/views/design/pages/muster/resource-teaser.blade.php',
        );
});

it('normalizes generator names consistently', function () {
    expect(
        app(MakeVdbsComponentCommand::class)
            ->normalizeName('Content Highlight')
    )->toBe('content-highlight')
        ->and(
            app(MakeVdbsPatternCommand::class)
                ->normalizeName('Resource Teaser')
        )
        ->toBe('resource-teaser');
});

it('passes the web content library integrity check', function () {
    expect(
        Artisan::call('vdbs:library-check')
    )->toBe(0);
});
