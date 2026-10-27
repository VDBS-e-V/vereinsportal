<?php

use App\Support\ErrorPageLinks;
use Illuminate\Support\Facades\Blade;

it('offers several helpful links for every error page', function () {
    foreach (['401', '403', '404', '419', '429', '500', '503'] as $code) {
        $links = ErrorPageLinks::for($code);

        expect(count($links))->toBeGreaterThanOrEqual(2);

        foreach ($links as $link) {
            expect($link)->toHaveKeys(['label', 'url']);
        }

        $view = file_get_contents(
            resource_path('views/errors/'.$code.'.blade.php'),
        );

        expect($view)->toContain("ErrorPageLinks::for('".$code."')");
    }
});

it('renders the link list and drops targets that are already buttons', function () {
    $html = Blade::render(
        '<x-vdbs.error-page code="404" title="T" description="D" primary-label="Start" primary-url="/" :links="$links" />',
        [
            'links' => [
                ['label' => 'Zur Startseite', 'url' => '/'],
                ['label' => 'Anmelden', 'url' => '/anmelden'],
                ['label' => 'Passwort vergessen', 'url' => '/passwort/vergessen'],
            ],
        ],
    );

    expect($html)
        ->toContain('error-page__links')
        ->toContain('Das könnte Ihnen helfen')
        ->toContain('Anmelden')
        ->toContain('Passwort vergessen')
        ->not->toContain('>Zur Startseite</a>');
});

it('renders no link list without links', function () {
    $html = Blade::render(
        '<x-vdbs.error-page code="404" title="T" description="D" />',
    );

    expect($html)->not->toContain('error-page__links');
});

it('shows the image area in a fixed 3:2 ratio', function () {
    $css = file_get_contents(
        resource_path('css/vdbs/components/error-pages.css'),
    );

    expect($css)
        ->toContain('aspect-ratio: 3 / 2');
});
