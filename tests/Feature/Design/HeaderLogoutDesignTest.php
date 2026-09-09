<?php

it('renders header logout as a csrf protected post action', function () {
    $layout = file_get_contents(
        resource_path('views/components/layouts/public.blade.php'),
    );
    $header = file_get_contents(
        resource_path('views/components/vdbs/portal-header.blade.php'),
    );

    expect($layout)
        ->toContain("route('my.logout')")
        ->toContain("'method' => 'post'");

    expect($header)
        ->toContain('method="POST"')
        ->toContain('@csrf')
        ->toContain('account-link--button')
        ->toContain('mobile-menu__action');
});

it('styles the logout action like the surrounding profile menu entries', function () {
    $css = file_get_contents(
        resource_path('css/vdbs/parts/header.css'),
    );

    expect($css)
        ->toContain('.account-link--button')
        ->toContain('.mobile-menu__action')
        ->toContain('cursor: pointer');
});
