<?php

it('adds clear cross links to the public account entry pages', function () {
    $login = file_get_contents(
        resource_path('views/livewire/identity/login.blade.php'),
    );
    $forgot = file_get_contents(
        resource_path('views/livewire/identity/password-forgot.blade.php'),
    );
    $reset = file_get_contents(
        resource_path('views/livewire/identity/password-reset.blade.php'),
    );
    $registration = file_get_contents(
        resource_path('views/livewire/identity/registration.blade.php'),
    );

    expect($login)
        ->toContain("route('my.registration.create')")
        ->toContain('Noch kein Konto? Registrieren')
        ->toContain('Melden Sie sich mit Ihrer E-Mail-Adresse');

    expect($forgot)
        ->toContain("route('my.login')")
        ->toContain('Zurück zur Anmeldung');

    expect($reset)
        ->toContain('Legen Sie ein neues Passwort');

    expect($registration)
        ->toContain("route('my.login')")
        ->toContain('Bereits registriert? Anmelden');
});
