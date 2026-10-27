<?php

use App\Support\ErrorPageChrome;

it('renders the normal portal header and footer on error pages', function () {
    $html = view('errors.404')->render();

    expect($html)
        ->toContain('vdbs-portal-header')
        ->toContain('vdbs-footer')
        ->toContain('id="error-content"')
        ->toContain('Seite nicht gefunden');
});

it('builds the guest header for server errors without session or database', function () {
    $header = ErrorPageChrome::header(503, 'Vorübergehend nicht verfügbar');

    expect($header)
        ->not->toBeNull()
        ->toContain('vdbs-portal-header')
        ->toContain('Vorübergehend nicht verfügbar');
});

it('provides the portal footer for error pages', function () {
    expect(ErrorPageChrome::footer())
        ->not->toBeNull()
        ->toContain('vdbs-footer');
});

it('falls back to the reduced shell when the portal chrome cannot be rendered', function () {
    $layout = file_get_contents(
        resource_path('views/errors/layout.blade.php'),
    );
    $chrome = file_get_contents(
        app_path('Support/ErrorPageChrome.php'),
    );

    expect($layout)
        ->toContain('ErrorPageChrome::header')
        ->toContain('ErrorPageChrome::footer')
        ->toContain("@include('errors.partials.fallback-header')")
        ->toContain("@include('errors.partials.fallback-footer')");

    expect($chrome)
        ->toContain('catch (Throwable)')
        ->toContain('SERVER_ERROR_FROM = 500');

    expect(resource_path('views/errors/partials/fallback-header.blade.php'))
        ->toBeFile();
    expect(resource_path('views/errors/partials/fallback-footer.blade.php'))
        ->toBeFile();
});
