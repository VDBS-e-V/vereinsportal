<?php

use Illuminate\Support\Facades\Blade;

it('renders the error page without an image by default', function () {
    $html = Blade::render(
        '<x-vdbs.error-page code="404" title="Titel" description="Text" />',
    );

    expect($html)
        ->not->toContain('error-page--bild')
        ->not->toContain('error-page__bild')
        ->toContain('aria-hidden="true"')
        ->toContain('Fehlercode 404');
});

it('renders the image variant with a decorative or described image', function () {
    $decorative = Blade::render(
        '<x-vdbs.error-page code="404" title="Titel" description="Text" image="/bild.png" />',
    );

    expect($decorative)
        ->toContain('error-page--bild')
        ->toContain('src="/bild.png"')
        ->toContain('alt=""')
        ->toContain('Fehlercode 404');

    $described = Blade::render(
        '<x-vdbs.error-page code="404" title="Titel" description="Text" image="/bild.png" image-alt="Eine leere Straße" />',
    );

    expect($described)
        ->toContain('alt="Eine leere Straße"');
});

it('provides styles and a reference for the image-text component', function () {
    $css = file_get_contents(
        resource_path('css/vdbs/v2/teaser.css'),
    );
    $page = file_get_contents(
        resource_path('views/design/pages/komponenten.blade.php'),
    );
    $errorCss = file_get_contents(
        resource_path('css/vdbs/components/error-pages.css'),
    );

    expect($css)
        ->toContain('.vdbs-bildtext {')
        ->toContain('.vdbs-bildtext--rechts');

    expect($page)
        ->toContain('id="bildtext"')
        ->toContain('vdbs-bildtext--rechts');

    expect($errorCss)
        ->toContain('.error-page--bild')
        ->toContain('.vdbs-error-page--bild');
});

it('uses the error page illustrations and tolerates a missing 429 image', function () {
    foreach (['404', '500', '401', '403'] as $code) {
        $view = file_get_contents(
            resource_path('views/errors/'.$code.'.blade.php'),
        );

        expect($view)
            ->toContain("asset('images/fehlerseiten/".$code.".webp')")
            ->toContain('image-alt=');

        expect(public_path('images/fehlerseiten/'.$code.'.webp'))
            ->toBeFile();
    }

    $view429 = file_get_contents(
        resource_path('views/errors/429.blade.php'),
    );

    expect($view429)
        ->toContain('code="429"')
        ->toContain("file_exists(public_path('images/fehlerseiten/429.webp'))");
});
