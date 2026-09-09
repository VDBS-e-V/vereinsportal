<?php

it('preserves strong focus indicators on form controls', function () {
    $forms = file_get_contents(
        resource_path(
            'css/vdbs/components/forms.css',
        ),
    );

    expect($forms)
        ->toContain('.form__control:focus-visible')
        ->toContain('outline: 3px solid var(--color-focus)')
        ->toContain("input[type='checkbox']:focus-visible");
});

it('keeps interactive targets usable on narrow and touch layouts', function () {
    $a11y = file_get_contents(
        resource_path('css/vdbs/a11y.css'),
    );
    $dialogs = file_get_contents(
        resource_path(
            'css/vdbs/components/dialogs.css',
        ),
    );

    expect($a11y)
        ->toContain('.disclosure__summary')
        ->toContain('[hidden]')
        ->and($dialogs)
        ->toContain('.dialog__actions .btn')
        ->toContain('width: 100%');
});
