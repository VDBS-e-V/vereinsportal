<?php

use Illuminate\Support\Facades\Blade;

it('renders the breadcrumb below the visual portal header', function () {
    $html = Blade::render(
        <<<'BLADE'
<x-vdbs.portal-header
    area="VDBS Portal"
    page-title="Start"
    :breadcrumbs="[
        ['label' => 'Start', 'url' => null],
    ]"
/>
BLADE
    );

    $headerEnd = strpos($html, '</header>');
    $breadcrumb = strpos(
        $html,
        'header-breadcrumb vdbs-portal-breadcrumb-bar',
    );

    expect($headerEnd)->not->toBeFalse()
        ->and($breadcrumb)->not->toBeFalse()
        ->and($breadcrumb > $headerEnd)->toBeTrue()
        ->and($html)->toContain(
            'header-breadcrumb__inner vdbs-portal-breadcrumb-bar__inner '.
            'layout-frame layout-frame--normal layout-frame--gutter'
        );
});
