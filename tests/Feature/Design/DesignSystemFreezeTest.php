<?php

use Illuminate\Support\Facades\Artisan;

it('marks the web content library as design system v1', function () {
    expect(config('web_content_library.version'))
        ->toBe('1.0.0')
        ->and(config('web_content_library.frozen'))
        ->toBeTrue()
        ->and(config('web_content_library.freeze_policy'))
        ->toContain('realen');
});

it('registers a design status command for the frozen system', function () {
    expect(array_keys(Artisan::all()))
        ->toContain('vdbs:design-status')
        ->and(
            Artisan::call(
                'vdbs:design-status',
            )
        )
        ->toBe(0);
});

it('surfaces the freeze status in the library', function () {
    $view = file_get_contents(
        resource_path(
            'views/design/pages/bibliothek/index.blade.php',
        ),
    );

    expect($view)
        ->toContain('Designsystem v{{ $libraryVersion }}')
        ->toContain('Stabilitätsmodus');
});
