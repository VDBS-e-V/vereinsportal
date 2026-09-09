<?php

it('defines the core button variants and states', function () {
    $buttons = file_get_contents(
        resource_path('css/vdbs/components/buttons.css'),
    );

    foreach ([
        '.btn--secondary',
        '.btn--accent',
        '.btn--quiet',
        '.btn--danger',
        '.btn--icon',
        ".btn[aria-busy='true']",
        '.btn__spinner',
        '.button-group',
    ] as $selector) {
        expect($buttons)->toContain($selector);
    }

    expect($buttons)
        ->toContain('min-height: var(--control-height-md);')
        ->toContain('min-height: var(--control-height-sm);')
        ->toContain('min-height: var(--control-height-lg);');
});

it('defines the core form states', function () {
    $forms = file_get_contents(
        resource_path('css/vdbs/components/forms.css'),
    );

    foreach ([
        '.form__optional',
        ".form__control[aria-invalid='true']",
        '.form__field--error',
        '.form__field--success',
        '.form__fieldset',
        '.form__legend',
        '.form__choice',
        '.form__control:disabled',
        '.form__control[readonly]',
    ] as $selector) {
        expect($forms)->toContain($selector);
    }
});

it('documents core buttons and form states in the design workbench', function () {
    $buttons = file_get_contents(
        resource_path('views/design/pages/elemente/buttons.blade.php'),
    );
    $forms = file_get_contents(
        resource_path('views/design/pages/elemente/formulare.blade.php'),
    );

    expect($buttons)
        ->toContain('btn--accent')
        ->toContain('btn--danger')
        ->toContain('btn--icon')
        ->toContain('aria-busy="true"');

    expect($forms)
        ->toContain('form__field--error')
        ->toContain('aria-invalid="true"')
        ->toContain('form__field--success')
        ->toContain('form__fieldset');
});
