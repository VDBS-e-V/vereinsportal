<?php

use App\Modules\Communication\Exceptions\EmailTemplateRenderException;
use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\EmailTemplatePlaceholder;
use App\Modules\Communication\Models\EmailTemplateVersion;
use App\Modules\Communication\Services\EmailTemplateRenderer;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;

function createRenderableEmailTemplate(
    string $subject,
    string $html,
): EmailTemplateVersion {
    $user = User::query()->create([
        'email' => fake()->unique()->safeEmail(),
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    $template = EmailTemplate::query()->create([
        'key' => 'auth.registration.verify',
        'name' => 'Registrierung bestätigen',
        'is_active' => true,
        'draft_subject' => $subject,
        'draft_html' => $html,
        'updated_by_user_id' => $user->id,
    ]);

    EmailTemplatePlaceholder::query()->create([
        'email_template_id' => $template->id,
        'key' => 'verification_url',
        'label' => 'Verifikationslink',
        'description' => 'Link zum Abschluss der Registrierung.',
        'example_value' => 'https://my.vdb.test/verify/example',
        'is_required' => true,
        'is_active' => true,
    ]);

    EmailTemplatePlaceholder::query()->create([
        'email_template_id' => $template->id,
        'key' => 'expires_at',
        'label' => 'Ablaufzeitpunkt',
        'description' => 'Zeitpunkt, bis zu dem der Link gültig ist.',
        'example_value' => '04.09.2026 12:00',
        'is_required' => true,
        'is_active' => true,
    ]);

    EmailTemplatePlaceholder::query()->create([
        'email_template_id' => $template->id,
        'key' => 'first_name',
        'label' => 'Vorname',
        'description' => 'Vorname der empfangenden Person.',
        'example_value' => 'Erika',
        'is_required' => false,
        'is_active' => true,
    ]);

    return EmailTemplateVersion::query()->create([
        'email_template_id' => $template->id,
        'version' => 1,
        'subject' => $subject,
        'html' => $html,
        'published_by_user_id' => $user->id,
        'published_at' => now(),
    ]);
}

it('renders whitelisted placeholders in subject and html', function () {
    $version = createRenderableEmailTemplate(
        subject: 'Hallo {{ first_name }}',
        html: <<<'HTML'
<p>Hallo {{ first_name }}</p>
<a href="{{ verification_url }}">Bestätigen</a>
<p>Gültig bis {{ expires_at }}</p>
HTML,
    );

    $rendered = app(EmailTemplateRenderer::class)->render(
        version: $version,
        values: [
            'first_name' => 'Erika & Co',
            'verification_url' => 'https://example.test/verify?a=1&b=2',
            'expires_at' => '04.09.2026 12:00',
        ],
    );

    expect($rendered['subject'])
        ->toBe('Hallo Erika & Co')
        ->and($rendered['html'])
        ->toContain('Hallo Erika &amp; Co')
        ->and($rendered['html'])
        ->toContain(
            'https://example.test/verify?a=1&amp;b=2'
        )
        ->and($rendered['html'])
        ->not->toContain('{{');
});

it('rejects an unknown placeholder used by the template', function () {
    $version = createRenderableEmailTemplate(
        subject: 'Registrierung',
        html: <<<'HTML'
<p>{{ verification_url }}</p>
<p>{{ expires_at }}</p>
<p>{{ secret_token }}</p>
HTML,
    );

    expect(
        fn () => app(EmailTemplateRenderer::class)->render(
            version: $version,
            values: [
                'verification_url' => 'https://example.test',
                'expires_at' => '04.09.2026',
            ],
        )
    )->toThrow(EmailTemplateRenderException::class);
});

it('rejects a template that omits a required placeholder', function () {
    $version = createRenderableEmailTemplate(
        subject: 'Registrierung',
        html: '<p>{{ verification_url }}</p>',
    );

    expect(
        fn () => app(EmailTemplateRenderer::class)->render(
            version: $version,
            values: [
                'verification_url' => 'https://example.test',
            ],
        )
    )->toThrow(EmailTemplateRenderException::class);
});

it('rejects rendering when a used placeholder value is missing', function () {
    $version = createRenderableEmailTemplate(
        subject: 'Registrierung',
        html: <<<'HTML'
<p>{{ verification_url }}</p>
<p>{{ expires_at }}</p>
HTML,
    );

    expect(
        fn () => app(EmailTemplateRenderer::class)->render(
            version: $version,
            values: [
                'verification_url' => 'https://example.test',
            ],
        )
    )->toThrow(EmailTemplateRenderException::class);
});

it('rejects values for placeholders not whitelisted by the template', function () {
    $version = createRenderableEmailTemplate(
        subject: 'Registrierung',
        html: <<<'HTML'
<p>{{ verification_url }}</p>
<p>{{ expires_at }}</p>
HTML,
    );

    expect(
        fn () => app(EmailTemplateRenderer::class)->render(
            version: $version,
            values: [
                'verification_url' => 'https://example.test',
                'expires_at' => '04.09.2026',
                'password' => 'Nie hier hinein',
            ],
        )
    )->toThrow(EmailTemplateRenderException::class);
});