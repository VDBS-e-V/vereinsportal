<?php

it('keeps every final page template in the design system', function () {
    foreach ([
        'artikel',
        'formular',
        'oeffentliche-uebersicht',
        'veranstaltung',
        'verwaltung-liste',
        'verwaltung-detail',
    ] as $template) {
        expect(
            file_exists(
                resource_path(
                    'views/design/pages/vorlagen/'.$template.'.blade.php',
                )
            )
        )->toBeTrue();
    }
});

it('keeps the central runtime error templates available', function () {
    foreach ([
        403,
        404,
        419,
        500,
        503,
    ] as $code) {
        expect(
            file_exists(
                resource_path(
                    'views/errors/'.$code.'.blade.php',
                )
            )
        )->toBeTrue();
    }
});

it('hardens print output for long content and semantic states', function () {
    $print = file_get_contents(
        resource_path(
            'css/vdbs/print.css',
        ),
    );
    $errors = file_get_contents(
        resource_path(
            'css/vdbs/components/error-pages.css',
        ),
    );

    expect($print)
        ->toContain('white-space: pre-wrap !important')
        ->toContain('break-after: avoid-page')
        ->toContain('.status,')
        ->and($errors)
        ->toContain('.error-page__actions .btn')
        ->toContain('width: 100%');
});
