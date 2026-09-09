<?php

it('ships cross component accessibility fallbacks', function () {
    $app = file_get_contents(
        resource_path('css/app.css'),
    );
    $a11y = file_get_contents(
        resource_path('css/vdbs/a11y.css'),
    );

    expect($app)
        ->toContain('vdbs/a11y.css');

    expect($a11y)
        ->toContain('@media (prefers-reduced-motion: reduce)')
        ->toContain('@media (prefers-contrast: more)')
        ->toContain('@media (forced-colors: active)')
        ->toContain('@media (pointer: coarse)')
        ->toContain('.vdbs-sr-only')
        ->toContain('overflow-wrap: anywhere');
});

it('migrates core public auth forms to explicit design system classes', function () {
    foreach ([
        'livewire/identity/login.blade.php',
        'livewire/identity/password-forgot.blade.php',
        'livewire/identity/password-reset.blade.php',
        'livewire/identity/two-factor-challenge.blade.php',
    ] as $view) {
        $content = file_get_contents(
            resource_path('views/'.$view),
        );

        expect($content)
            ->toContain('portal-page__form form')
            ->toContain('form__control')
            ->toContain('form__label')
            ->toContain('class="btn');
    }
});

it('connects auth field errors to their controls', function () {
    $login = file_get_contents(
        resource_path('views/livewire/identity/login.blade.php'),
    );
    $forgot = file_get_contents(
        resource_path('views/livewire/identity/password-forgot.blade.php'),
    );
    $reset = file_get_contents(
        resource_path('views/livewire/identity/password-reset.blade.php'),
    );
    $twoFactor = file_get_contents(
        resource_path('views/livewire/identity/two-factor-challenge.blade.php'),
    );

    expect($login)
        ->toContain('aria-describedby="login-email-error"')
        ->toContain('aria-describedby="login-password-error"');

    expect($forgot)
        ->toContain('aria-describedby="password-forgot-email-error"');

    expect($reset)
        ->toContain('aria-describedby="password-reset-password-error"');

    expect($twoFactor)
        ->toContain('aria-describedby="two-factor-email-error"')
        ->toContain('aria-describedby="two-factor-totp-error"')
        ->toContain('aria-describedby="two-factor-recovery-error"')
        ->toContain('pattern="[0-9]{6}"');
});

it('uses the reusable notice component on migrated auth pages', function () {
    foreach ([
        'livewire/identity/login.blade.php',
        'livewire/identity/password-forgot.blade.php',
        'livewire/identity/password-reset.blade.php',
        'livewire/identity/two-factor-challenge.blade.php',
    ] as $view) {
        $content = file_get_contents(
            resource_path('views/'.$view),
        );

        expect($content)
            ->toContain('<x-vdbs.notice');
    }
});
