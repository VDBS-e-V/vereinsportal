<?php

it('uses explicit design system fields on registration', function () {
    $registration = file_get_contents(
        resource_path('views/livewire/identity/registration.blade.php'),
    );

    expect($registration)
        ->toContain('portal-page__form form')
        ->toContain('class="form__field"')
        ->toContain('class="form__control"')
        ->toContain('class="form__fieldset"')
        ->toContain('class="form__choice"')
        ->toContain('registration-password-help')
        ->toContain('aria-invalid="true"');
});

it('uses shared notices throughout registration status and result pages', function () {
    foreach ([
        'livewire/identity/registration-status.blade.php',
        'identity/registration/verification-failed.blade.php',
        'identity/registration/verified.blade.php',
    ] as $view) {
        $content = file_get_contents(
            resource_path('views/'.$view),
        );

        expect($content)
            ->toContain('<x-vdbs.notice');
    }
});

it('provides clear next actions after registration verification', function () {
    $failed = file_get_contents(
        resource_path('views/identity/registration/verification-failed.blade.php'),
    );
    $verified = file_get_contents(
        resource_path('views/identity/registration/verified.blade.php'),
    );

    expect($failed)
        ->toContain("route('my.registration.create')")
        ->toContain('class="btn"');

    expect($verified)
        ->toContain("route('my.login')")
        ->toContain('class="btn"');
});

it('uses the shared danger zone and metadata patterns for account deletion', function () {
    $deletion = file_get_contents(
        resource_path('views/livewire/identity/account-deletion.blade.php'),
    );

    expect($deletion)
        ->toContain('<x-vdbs.danger-zone')
        ->toContain('class="metadata-list"')
        ->toContain('<x-vdbs.status')
        ->toContain('<x-vdbs.notice')
        ->toContain('class="btn btn--danger"');
});
