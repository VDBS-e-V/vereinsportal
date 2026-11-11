<?php

/** Tokens der Eingabefelder: Fläche und deaktiviert, hell und in beiden Dark-Blöcken (docs/61_TEXTFELDER_KRITERIEN.md). */
it('defines the field tokens for the light theme and both dark blocks', function () {
    $css = (string) file_get_contents(resource_path('css/vdbs/tokens.css'));

    foreach (['--field-bg', '--field-disabled-bg', '--field-disabled-fg'] as $token) {
        expect(substr_count($css, $token.':'))->toBe(3, $token);
    }

    expect($css)->toContain('--field-bg: var(--color-weiss);')->toContain('--field-bg: var(--color-graphit-900);');
});

it('ships the in-browser validation and loads it', function () {
    $js = (string) file_get_contents(resource_path('js/validation.js'));
    $app = (string) file_get_contents(resource_path('js/app.js'));

    expect($app)->toContain("import './validation';")
        ->and($js)->toContain('data-vdbs-validate')->toContain('focusout')->toContain("'submit'")->toContain('aria-invalid')->toContain('Bitte füllen Sie dieses Feld aus.')->toContain('window.vdbsValidateFields');
});
