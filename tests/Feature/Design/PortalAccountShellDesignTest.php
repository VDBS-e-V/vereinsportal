<?php

it('groups authenticated account pages under a single role-aware header item', function () {
    $layout = file_get_contents(
        resource_path('views/components/layouts/public.blade.php'),
    );

    expect($layout)
        ->toContain("'label' => 'Konto'")
        ->toContain("'children' => \$accountAreaNavigation")
        ->toContain("'label' => 'Mein Profil'")
        ->toContain("route('my.account.profile')")
        ->toContain("'label' => 'Kontoeinstellungen'")
        ->toContain("'label' => 'Mitgliedschaft'")
        ->toContain("'label' => 'Teamendeneinstellungen'")
        ->toContain('RoleKey::Member')
        ->toContain('RoleKey::Team')
        ->toContain('request()->routeIs(...$accountRouteNames)')
        ->toContain('aria-label="Kontoeinstellungen"')
        ->toContain('class="local-nav__link"');
});

it('shows the public start navigation to guests', function () {
    $layout = file_get_contents(
        resource_path('views/components/layouts/public.blade.php'),
    );
    $access = file_get_contents(
        resource_path('views/livewire/portal/access.blade.php'),
    );

    expect($layout)
        ->toContain("'label' => 'Über das Portal'")
        ->toContain("'label' => 'Zugang zum Portal'")
        ->toContain("'label' => 'FAQ'")
        ->toContain("'label' => 'Kontakt'")
        ->toContain('$navigation = $portalNavigation;')
        ->and($access)
        ->toContain('Antrag - Zugang zum Portal')
        ->toContain('Verbindung zum Verein')
        ->toContain('Kontaktdaten')
        ->toContain('Antrag absenden');
});

it('turns the portal home into the issue 50 start overview', function () {
    $home = file_get_contents(
        resource_path('views/livewire/identity/home.blade.php'),
    );

    expect($home)
        ->toContain('Willkommen im VDBS Serviceportal')
        ->toContain('Das VDBS Serviceportal')
        ->toContain('Empfohlene Artikel')
        ->toContain("route('portal.about')")
        ->toContain("route('portal.access')")
        ->toContain("route('portal.faq')")
        ->toContain("route('portal.contact')")
        ->toContain('Mein Konto')
        ->toContain('Hilfe');
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

it('provides account profile and settings overview pages', function () {
    $account = file_get_contents(
        resource_path('views/livewire/identity/account.blade.php'),
    );
    $profile = file_get_contents(
        resource_path('views/livewire/identity/account-profile.blade.php'),
    );
    $settings = file_get_contents(
        resource_path('views/livewire/identity/account-settings.blade.php'),
    );

    expect($account)
        ->toContain('Mein Profil')
        ->toContain("route('my.account.profile')")
        ->toContain('Kontoeinstellungen')
        ->toContain('Mitgliedschaft')
        ->toContain('Teamendeneinstellungen')
        ->toContain('Meine Tickets')
        ->and($profile)
        ->toContain('Mein Profil')
        ->toContain('Profilbild')
        ->toContain("route('my.account.avatar.store')")
        ->toContain("route('my.account.avatar.delete')")
        ->toContain('enctype="multipart/form-data"')
        ->not->toContain('wire:model="avatar"')
        ->toContain('image/jpeg,image/png,image/webp')
        ->and($settings)
        ->toContain('Kontodaten')
        ->toContain('2FA')
        ->toContain('E-Mail-Änderung')
        ->toContain('Passwort ändern')
        ->toContain('Konto löschen');
});
