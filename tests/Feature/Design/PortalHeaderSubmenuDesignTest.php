<?php

it('uses parent navigation items as overview links without duplicate overview entries', function () {
    $header = file_get_contents(
        resource_path('views/components/vdbs/portal-header.blade.php'),
    );

    expect($header)
        ->toContain('header-submenu-trigger--area')
        ->toContain('header-submenu-trigger--page')
        ->toContain('href="{{ $url }}"')
        ->not->toContain('<a href="{{ $url }}">Übersicht</a>');
});

it('supports hover and keyboard access for header submenus', function () {
    $script = file_get_contents(
        resource_path('js/portal-header.js'),
    );

    expect($script)
        ->toContain("(hover: hover) and (pointer: fine)")
        ->toContain("addEventListener('mouseenter'")
        ->toContain("addEventListener('mouseleave'")
        ->toContain("addEventListener('focusin'")
        ->toContain("addEventListener('focusout'");
});
