<?php

it('uses explicit form components on authenticated account settings pages', function () {
    foreach ([
        'livewire/identity/profile.blade.php',
        'livewire/identity/email-change.blade.php',
        'livewire/identity/password-change.blade.php',
        'livewire/identity/security.blade.php',
    ] as $view) {
        $content = file_get_contents(
            resource_path('views/'.$view),
        );

        expect($content)
            ->toContain('form__control')
            ->toContain('form__label')
            ->toContain('class="btn');
    }
});

it('connects authenticated account field errors to their controls', function () {
    $profile = file_get_contents(
        resource_path('views/livewire/identity/profile.blade.php'),
    );
    $email = file_get_contents(
        resource_path('views/livewire/identity/email-change.blade.php'),
    );
    $password = file_get_contents(
        resource_path('views/livewire/identity/password-change.blade.php'),
    );
    $security = file_get_contents(
        resource_path('views/livewire/identity/security.blade.php'),
    );

    expect($profile)
        ->toContain('aria-describedby="profile-first-name-error"')
        ->toContain('aria-describedby="profile-country-code-error"');

    expect($email)
        ->toContain('aria-describedby="email-change-new-email-error"');

    expect($password)
        ->toContain('aria-describedby="password-change-current-error"')
        ->toContain('aria-describedby="password-change-new-error"');

    expect($security)
        ->toContain('aria-describedby="security-totp-code-error"')
        ->toContain('pattern="[0-9]{6}"');
});

it('uses shared notices and statuses on account security flows', function () {
    $security = file_get_contents(
        resource_path('views/livewire/identity/security.blade.php'),
    );
    $emailSecurity = file_get_contents(
        resource_path('views/livewire/identity/email-change-security.blade.php'),
    );

    expect($security)
        ->toContain('<x-vdbs.notice')
        ->toContain('<x-vdbs.status');

    expect($emailSecurity)
        ->toContain('<x-vdbs.notice type="warning">');
});
