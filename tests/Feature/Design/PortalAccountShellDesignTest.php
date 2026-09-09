<?php

it('groups authenticated account pages under a single header item', function () {
    $layout = file_get_contents(
        resource_path('views/components/layouts/public.blade.php'),
    );

    expect($layout)
        ->toContain("'label' => 'Konto'")
        ->toContain("'children' => collect(\$accountNavigation)")
        ->toContain("request()->routeIs(...\$accountRouteNames)")
        ->toContain('aria-label="Kontoeinstellungen"')
        ->toContain('class="local-nav__link"');
});

it('groups guest entry pages under Zugang', function () {
    $layout = file_get_contents(
        resource_path('views/components/layouts/public.blade.php'),
    );

    expect($layout)
        ->toContain("'label' => 'Zugang'")
        ->toContain("'label' => 'Registrieren'")
        ->toContain("'label' => 'Passwort vergessen'");
});

it('turns the portal home into a useful account overview', function () {
    $home = file_get_contents(
        resource_path('views/livewire/identity/home.blade.php'),
    );

    expect($home)
        ->toContain('Schnellzugriff')
        ->toContain('Kontoinformationen')
        ->toContain("route('my.profile')")
        ->toContain("route('my.security')")
        ->toContain('<x-vdbs.resource-item')
        ->toContain('<x-vdbs.status');
});

it('uses hierarchical account breadcrumbs for nested settings', function () {
    $layout = file_get_contents(
        resource_path('views/components/layouts/public.blade.php'),
    );

    expect($layout)
        ->toContain("'label' => 'VDBS Portal'")
        ->toContain("'label' => 'Konto'")
        ->toContain("'url' => route('my.profile')")
        ->toContain("'label' => \$pageTitle");
});
