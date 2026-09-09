<?php

it('keeps long header menus usable on smaller screens', function () {
    $header = file_get_contents(
        resource_path('css/vdbs/parts/header.css'),
    );
    $component = file_get_contents(
        resource_path('views/components/vdbs/portal-header.blade.php'),
    );
    $navigation = file_get_contents(
        resource_path('css/vdbs/components/navigation.css'),
    );

    expect($header)
        ->toContain('max-height: min(70vh, 34rem)')
        ->toContain('max-height: calc(100dvh - 4.5rem)')
        ->toContain('overscroll-behavior: contain')
        ->toContain("a[aria-current='page']");

    expect($component)
        ->toContain('$mobileCurrent')
        ->toContain("(\$child['active'] ?? false) === true")
        ->toContain('aria-current="page"');

    expect($navigation)
        ->toContain('.account-local-navigation')
        ->toContain('overscroll-behavior-inline: contain');
});

it('stacks important page actions on very small screens', function () {
    $pages = file_get_contents(
        resource_path('css/vdbs/pages.css'),
    );

    expect($pages)
        ->toContain('@media (max-width: 36rem)')
        ->toContain('.portal-page__actions > .btn:not(.btn--quiet)')
        ->toContain('width: 100%');
});
