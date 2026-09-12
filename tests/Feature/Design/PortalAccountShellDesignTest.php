<?php

it('groups authenticated account pages under a single role-aware header item', function () {
    $layout = file_get_contents(
        resource_path('views/components/layouts/public.blade.php'),
    );

    expect($layout)
        ->toContain("'label' => 'Konto'")
        ->toContain("'children' => \$accountAreaNavigation")
        ->toContain("'label' => 'Kontoeinstellungen'")
        ->toContain("'label' => 'Mitgliedschaft'")
        ->toContain("'label' => 'Teamendeneinstellungen'")
        ->toContain('RoleKey::Member')
        ->toContain('RoleKey::Team')
        ->toContain('request()->routeIs(...$accountRouteNames)')
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
        ->toContain("route('my.account')")
        ->toContain("route('my.account.settings')")
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
        ->toContain("'url' => route('my.account')")
        ->toContain("'label' => 'Kontoeinstellungen'")
        ->toContain("'url' => route('my.account.settings')")
        ->toContain("'label' => \$pageTitle");
});

it('provides account and settings overview pages', function () {
    $account = file_get_contents(
        resource_path('views/livewire/identity/account.blade.php'),
    );
    $settings = file_get_contents(
        resource_path('views/livewire/identity/account-settings.blade.php'),
    );

    expect($account)
        ->toContain('Mein Profil')
        ->toContain('Kontoeinstellungen')
        ->toContain('Mitgliedschaft')
        ->toContain('Teamendeneinstellungen')
        ->toContain('Meine Tickets')
        ->and($settings)
        ->toContain('Kontodaten')
        ->toContain('2FA')
        ->toContain('E-Mail-Änderung')
        ->toContain('Passwort ändern')
        ->toContain('Konto löschen');
});
