<?php

it('uses the shared VDBS application page structure on all routed page views', function () {
    $views = [
        'livewire/identity/account-deletion.blade.php',
        'livewire/identity/email-change-security.blade.php',
        'livewire/identity/email-change.blade.php',
        'livewire/identity/home.blade.php',
        'livewire/identity/login.blade.php',
        'livewire/identity/password-change.blade.php',
        'livewire/identity/password-forgot.blade.php',
        'livewire/identity/password-reset.blade.php',
        'livewire/identity/profile.blade.php',
        'livewire/identity/registration-status.blade.php',
        'livewire/identity/registration.blade.php',
        'livewire/identity/security.blade.php',
        'livewire/identity/two-factor-challenge.blade.php',
        'identity/registration/verification-failed.blade.php',
        'identity/registration/verified.blade.php',
    ];

    foreach ($views as $view) {
        $content = file_get_contents(
            resource_path('views/'.$view),
        );

        expect($content)
            ->toContain('portal-page')
            ->not->toContain('class="card"');
    }
});

it('uses the normal VDBS frame for application content', function () {
    $layout = file_get_contents(
        resource_path(
            'views/components/layouts/public.blade.php',
        ),
    );

    expect($layout)
        ->toContain(
            '<x-vdbs.frame width="normal" gutter="both">',
        );
});
