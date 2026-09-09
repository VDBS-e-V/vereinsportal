<?php

it('does not rely on broad implicit form selectors anymore', function () {
    $forms = file_get_contents(
        resource_path('css/vdbs/components/forms.css'),
    );

    expect($forms)
        ->not->toContain('.site-main input')
        ->not->toContain('.design-main input')
        ->not->toContain('.site-main label')
        ->not->toContain('.field--error')
        ->not->toContain('.field__error')
        ->toContain('.form__control')
        ->toContain('.vdbs-input');
});

it('does not style arbitrary unclassed buttons in product content', function () {
    $buttons = file_get_contents(
        resource_path('css/vdbs/components/buttons.css'),
    );

    expect($buttons)
        ->not->toContain('button:not([class])')
        ->not->toContain('.site-main button[disabled]')
        ->not->toContain('.design-main button[disabled]')
        ->toContain('.btn,')
        ->toContain('.vdbs-button');
});

it('keeps search and filter labels explicit after fallback cleanup', function () {
    $search = file_get_contents(
        resource_path('css/vdbs/components/search.css'),
    );

    expect($search)
        ->toContain('.search-form__field > label')
        ->toContain('.filter-bar__field > label')
        ->toContain('font-weight: 700');
});

it('keeps migrated identity views on explicit form field classes', function () {
    foreach ([
        'login.blade.php',
        'password-forgot.blade.php',
        'password-reset.blade.php',
        'two-factor-challenge.blade.php',
        'registration.blade.php',
        'profile.blade.php',
        'email-change.blade.php',
        'password-change.blade.php',
        'security.blade.php',
    ] as $view) {
        $content = file_get_contents(
            resource_path('views/livewire/identity/'.$view),
        );

        expect($content)
            ->not->toContain('class="field"')
            ->not->toContain('field--choice');
    }
});
