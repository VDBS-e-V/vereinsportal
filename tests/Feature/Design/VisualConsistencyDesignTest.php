<?php

it('keeps form grids on one spacing rhythm', function () {
    $css = file_get_contents(
        resource_path(
            'css/vdbs/components/forms.css',
        ),
    );

    expect($css)
        ->toContain('.form__grid > .form__field')
        ->toContain('margin-bottom: 0')
        ->toContain('min-width: 0')
        ->toContain('overflow-wrap: anywhere');
});

it('hardens long table and mobile action content', function () {
    $tables = file_get_contents(
        resource_path(
            'css/vdbs/components/tables.css',
        ),
    );
    $contacts = file_get_contents(
        resource_path(
            'css/vdbs/components/contacts.css',
        ),
    );
    $files = file_get_contents(
        resource_path(
            'css/vdbs/components/files.css',
        ),
    );

    expect($tables)
        ->toContain('overscroll-behavior-inline: contain')
        ->toContain('overflow-wrap: anywhere')
        ->and($contacts)
        ->toContain('.contact-block__actions .btn')
        ->and($files)
        ->toContain('.file-item__actions .btn');
});
