<?php

use Illuminate\Support\Facades\Blade;

it('renders all supported layout frame options', function () {
    $html = Blade::render(
        <<<'BLADE'
<x-vdbs.frame width="small" gutter="both">Small</x-vdbs.frame>
<x-vdbs.frame width="normal" gutter="none">Normal</x-vdbs.frame>
<x-vdbs.frame width="wide" gutter="start">Wide</x-vdbs.frame>
<x-vdbs.frame width="full" gutter="end">Full</x-vdbs.frame>
BLADE
    );

    expect($html)
        ->toContain('layout-frame--small')
        ->toContain('layout-frame--gutter')
        ->toContain('layout-frame--normal')
        ->toContain('layout-frame--flush')
        ->toContain('layout-frame--wide')
        ->toContain('layout-frame--gutter-start')
        ->toContain('layout-frame--full')
        ->toContain('layout-frame--gutter-end');
});

it('uses full width for the header and normal width for the path', function () {
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

    expect(
        substr_count(
            $html,
            'layout-frame layout-frame--full layout-frame--gutter',
        )
    )
        ->toBe(3)
        ->and($html)
        ->toContain(
            'header-breadcrumb__inner vdbs-portal-breadcrumb-bar__inner '.
            'layout-frame layout-frame--normal layout-frame--gutter'
        );
});
