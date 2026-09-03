<?php

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Communication\Actions\PublishEmailTemplateAction;
use App\Modules\Communication\Exceptions\EmailTemplateCannotBePublished;
use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\EmailTemplatePlaceholder;
use App\Modules\Communication\Models\EmailTemplateVersion;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;

function createPublishableEmailTemplate(): array
{
    $publisher = User::query()->create([
        'email' => fake()->unique()->safeEmail(),
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    $template = EmailTemplate::query()->create([
        'key' => 'auth.registration.verify',
        'name' => 'Registrierung bestätigen',
        'is_active' => false,
        'draft_subject' => 'Hallo {{ first_name }}',
        'draft_html' => <<<'HTML'
<p onclick="alert('xss')">Hallo {{ first_name }}</p>
<a href="{{ verification_url }}">Bestätigen</a>
<p>Gültig bis {{ expires_at }}</p>
<script>alert('xss')</script>
HTML,
        'updated_by_user_id' => $publisher->id,
    ]);

    foreach ([
        [
            'key' => 'verification_url',
            'required' => true,
        ],
        [
            'key' => 'expires_at',
            'required' => true,
        ],
        [
            'key' => 'first_name',
            'required' => false,
        ],
        [
            'key' => 'privacy_notice_version',
            'required' => false,
        ],
        [
            'key' => 'support_email',
            'required' => false,
        ],
    ] as $placeholder) {
        EmailTemplatePlaceholder::query()->create([
            'email_template_id' => $template->id,
            'key' => $placeholder['key'],
            'label' => $placeholder['key'],
            'description' => $placeholder['key'],
            'example_value' => null,
            'is_required' => $placeholder['required'],
            'is_active' => true,
        ]);
    }

    return [
        $template,
        $publisher,
    ];
}

it('publishes a sanitized immutable email template version with audit', function () {
    [$template, $publisher] = createPublishableEmailTemplate();

    $version = app(
        PublishEmailTemplateAction::class
    )->execute(
        templateId: $template->id,
        publisher: $publisher,
        ipAddress: '192.0.2.10',
        userAgent: 'Pest Browser',
        deviceInfo: [
            'client' => 'test',
        ],
    );

    expect($version->version)
        ->toBe(1)
        ->and($version->subject)
        ->toBe('Hallo {{ first_name }}')
        ->and($version->html)
        ->toContain('{{ verification_url }}')
        ->and($version->html)
        ->toContain('{{ expires_at }}')
        ->and($version->html)
        ->not->toContain('<script')
        ->and($version->html)
        ->not->toContain('onclick')
        ->and($version->published_by_user_id)
        ->toBe($publisher->id)
        ->and($version->published_at)
        ->not->toBeNull();

    $audit = AuditEvent::query()
        ->where(
            'event_key',
            AuditEventCatalog::EMAIL_TEMPLATE_PUBLISHED,
        )
        ->sole();

    expect($audit->actor_type)
        ->toBe(AuditActorType::User)
        ->and($audit->actor_user_id)
        ->toBe($publisher->id)
        ->and($audit->subject_type)
        ->toBe('email_template_version')
        ->and($audit->subject_id)
        ->toBe($version->id)
        ->and($audit->new_values['version'])
        ->toBe(1)
        ->and($audit->new_values['key'])
        ->toBe('auth.registration.verify')
        ->and($audit->ip_address)
        ->toBe('192.0.2.10')
        ->and($audit->user_agent)
        ->toBe('Pest Browser')
        ->and($audit->device_info)
        ->toBe([
            'client' => 'test',
        ]);
});

it('creates consecutive immutable versions', function () {
    [$template, $publisher] = createPublishableEmailTemplate();

    $first = app(
        PublishEmailTemplateAction::class
    )->execute(
        templateId: $template->id,
        publisher: $publisher,
    );

    $template->update([
        'draft_subject' => 'Version zwei {{ first_name }}',
    ]);

    $second = app(
        PublishEmailTemplateAction::class
    )->execute(
        templateId: $template->id,
        publisher: $publisher,
    );

    expect($first->version)
        ->toBe(1)
        ->and($second->version)
        ->toBe(2)
        ->and($first->subject)
        ->toBe('Hallo {{ first_name }}')
        ->and($second->subject)
        ->toBe('Version zwei {{ first_name }}')
        ->and(
            EmailTemplateVersion::query()->count()
        )
        ->toBe(2)
        ->and(
            AuditEvent::query()
                ->where(
                    'event_key',
                    AuditEventCatalog::EMAIL_TEMPLATE_PUBLISHED,
                )
                ->count()
        )
        ->toBe(2);
});

it('rejects an unknown placeholder before publishing', function () {
    [$template, $publisher] = createPublishableEmailTemplate();

    $template->update([
        'draft_html' => <<<'HTML'
<p>{{ verification_url }}</p>
<p>{{ expires_at }}</p>
<p>{{ secret_token }}</p>
HTML,
    ]);

    expect(
        fn () => app(
            PublishEmailTemplateAction::class
        )->execute(
            templateId: $template->id,
            publisher: $publisher,
        )
    )->toThrow(
        EmailTemplateCannotBePublished::class
    );

    expect(EmailTemplateVersion::query()->count())
        ->toBe(0)
        ->and(
            AuditEvent::query()
                ->where(
                    'event_key',
                    AuditEventCatalog::EMAIL_TEMPLATE_PUBLISHED,
                )
                ->count()
        )
        ->toBe(0);
});

it('rejects publication when a required placeholder is missing', function () {
    [$template, $publisher] = createPublishableEmailTemplate();

    $template->update([
        'draft_html' => <<<'HTML'
<p>{{ verification_url }}</p>
HTML,
    ]);

    expect(
        fn () => app(
            PublishEmailTemplateAction::class
        )->execute(
            templateId: $template->id,
            publisher: $publisher,
        )
    )->toThrow(
        EmailTemplateCannotBePublished::class
    );

    expect(EmailTemplateVersion::query()->count())
        ->toBe(0)
        ->and(
            AuditEvent::query()
                ->where(
                    'event_key',
                    AuditEventCatalog::EMAIL_TEMPLATE_PUBLISHED,
                )
                ->count()
        )
        ->toBe(0);
});

it('rolls published version and audit back with an outer transaction', function () {
    [$template, $publisher] = createPublishableEmailTemplate();

    $startingTransactionLevel = DB::transactionLevel();

    DB::beginTransaction();

    try {
        app(
            PublishEmailTemplateAction::class
        )->execute(
            templateId: $template->id,
            publisher: $publisher,
        );

        expect(
            EmailTemplateVersion::query()->count()
        )->toBe(1)
            ->and(
                AuditEvent::query()
                    ->where(
                        'event_key',
                        AuditEventCatalog::EMAIL_TEMPLATE_PUBLISHED,
                    )
                    ->count()
            )
            ->toBe(1);
    } finally {
        if (
            DB::transactionLevel()
            > $startingTransactionLevel
        ) {
            DB::rollBack();
        }
    }

    expect(
        EmailTemplateVersion::query()->count()
    )->toBe(0)
        ->and(
            AuditEvent::query()
                ->where(
                    'event_key',
                    AuditEventCatalog::EMAIL_TEMPLATE_PUBLISHED,
                )
                ->count()
        )
        ->toBe(0);
});