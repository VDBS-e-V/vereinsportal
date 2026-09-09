<?php

use Illuminate\Support\Facades\Route;

it('registers the error pages design reference', function () {
    expect(Route::has('design.fehlerseiten'))
        ->toBeTrue();

    $design = file_get_contents(
        resource_path('views/design/pages/fehlerseiten/index.blade.php'),
    );

    expect($design)
        ->toContain('Fehlerseiten')
        ->toContain('404 · Seite nicht gefunden')
        ->toContain('403')
        ->toContain('419')
        ->toContain('500')
        ->toContain('503')
        ->toContain('<x-vdbs.error-page');
});

it('provides branded runtime views for the central http error states', function () {
    foreach ([
        403 => 'Zugriff nicht erlaubt',
        404 => 'Seite nicht gefunden',
        419 => 'Sitzung abgelaufen',
        500 => 'Es ist ein Fehler aufgetreten',
        503 => 'Vorübergehend nicht verfügbar',
    ] as $code => $title) {
        $path = resource_path(
            'views/errors/'.$code.'.blade.php',
        );

        expect(file_exists($path))
            ->toBeTrue();

        $view = file_get_contents($path);

        expect($view)
            ->toContain('<x-vdbs.error-page')
            ->toContain('code="'.$code.'"')
            ->toContain('title="'.$title.'"');
    }
});

it('keeps the error page component accessible and action oriented', function () {
    $component = file_get_contents(
        resource_path('views/components/vdbs/error-page.blade.php'),
    );
    $layout = file_get_contents(
        resource_path('views/errors/layout.blade.php'),
    );
    $css = file_get_contents(
        resource_path('css/vdbs/components/error-pages.css'),
    );
    $appCss = file_get_contents(
        resource_path('css/app.css'),
    );

    expect($component)
        ->toContain('aria-labelledby')
        ->toContain('error-page__actions')
        ->toContain('Fehlercode {{ $code }}')
        ->not->toContain('onclick=');

    expect($layout)
        ->toContain('lang="de"')
        ->toContain('vdbs-skip-link')
        ->toContain('id="error-content"');

    expect($css)
        ->toContain('.error-page')
        ->toContain('.error-page--compact')
        ->toContain('@media (max-width: 48rem)')
        ->toContain('@media print');

    expect($appCss)
        ->toContain('./vdbs/components/error-pages.css');
});

it('renders the standalone 404 error view with the shared design', function () {
    $html = view('errors.404')->render();

    expect($html)
        ->toContain('Seite nicht gefunden')
        ->toContain('Fehlercode 404')
        ->toContain('Zur Startseite')
        ->toContain('VDBS Portal');
});
