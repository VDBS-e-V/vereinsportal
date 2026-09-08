<?php

it('defines the shared foundation tokens used by components', function () {
    $tokens = file_get_contents(
        resource_path('css/vdbs/tokens.css'),
    );

    foreach ([
        '--color-text-disabled',
        '--color-surface-disabled',
        '--border-width-default',
        '--border-width-strong',
        '--border-width-accent',
        '--control-height-sm',
        '--control-height-md',
        '--control-height-lg',
        '--icon-size-sm',
        '--icon-size-md',
        '--icon-size-lg',
        '--z-header',
        '--z-dropdown',
        '--z-popover',
        '--z-overlay',
        '--transition-normal',
    ] as $token) {
        expect($tokens)->toContain($token);
    }
});

it('derives frame widths from the canonical layout tokens', function () {
    $frames = file_get_contents(
        resource_path('css/vdbs/frames.css'),
    );

    expect($frames)
        ->toContain(
            '--layout-width-small: var(--layout-content-small-max, 60rem);',
        )
        ->toContain(
            '--layout-width-normal: var(--layout-content-max, 80rem);',
        )
        ->toContain(
            '--layout-width-wide: var(--layout-content-wide-max, 90rem);',
        );
});

it('uses the canonical body line height on application pages', function () {
    $pages = file_get_contents(
        resource_path('css/vdbs/pages.css'),
    );

    expect($pages)
        ->toContain('line-height: var(--leading-body);')
        ->not->toContain('--line-height-body');
});

it('does not globally derive feedback colours from aria live-region roles', function () {
    $forms = file_get_contents(
        resource_path('css/vdbs/components/forms.css'),
    );

    expect($forms)
        ->not->toContain(".site-main [role='alert'],")
        ->not->toContain(".site-main [role='status'],");
});
