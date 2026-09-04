<?php

use App\Modules\Communication\Services\EmailTemplateHtmlSanitizer;

it('keeps safe email html and template placeholders', function () {
    $html = <<<'HTML'
<p>Hallo {{ first_name }}</p>
<p>
    <a href="{{ verification_url }}">
        Registrierung bestätigen
    </a>
</p>
<p>Gültig bis {{ expires_at }}</p>
HTML;

    $sanitized = app(
        EmailTemplateHtmlSanitizer::class
    )->sanitize($html);

    expect($sanitized)
        ->toContain('<p>')
        ->and($sanitized)
        ->toContain('{{ first_name }}')
        ->and($sanitized)
        ->toContain('{{ verification_url }}')
        ->and($sanitized)
        ->toContain('{{ expires_at }}')
        ->and($sanitized)
        ->toContain('href="{{ verification_url }}"');
});

it('removes script elements and javascript event handlers', function () {
    $html = <<<'HTML'
<p onclick="alert('xss')">Hallo</p>
<script>alert('xss')</script>
<p>Weiter</p>
HTML;

    $sanitized = app(
        EmailTemplateHtmlSanitizer::class
    )->sanitize($html);

    expect($sanitized)
        ->toContain('Hallo')
        ->and($sanitized)
        ->toContain('Weiter')
        ->and($sanitized)
        ->not->toContain('<script')
        ->and($sanitized)
        ->not->toContain('alert(')
        ->and($sanitized)
        ->not->toContain('onclick');
});

it('removes unsafe javascript links', function () {
    $html = <<<'HTML'
<p>
    <a href="javascript:alert('xss')">
        Unsicher
    </a>
</p>
HTML;

    $sanitized = app(
        EmailTemplateHtmlSanitizer::class
    )->sanitize($html);

    expect($sanitized)
        ->toContain('Unsicher')
        ->and($sanitized)
        ->not->toContain('javascript:')
        ->and($sanitized)
        ->not->toContain('alert(');
});
