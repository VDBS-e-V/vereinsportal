<?php

it('enables the logout action inside the design system header', function () {
    $layout = file_get_contents(
        resource_path('views/design/layout.blade.php'),
    );

    expect($layout)
        ->toContain("route('my.logout')")
        ->toContain("'method' => 'post'");
});

it('uses the shared csrf protected header logout rendering', function () {
    $header = file_get_contents(
        resource_path('views/components/vdbs/portal-header.blade.php'),
    );

    expect($header)
        ->toContain('account-logout-form')
        ->toContain('method="POST"')
        ->toContain('@csrf');
});