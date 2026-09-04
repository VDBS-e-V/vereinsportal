<?php

use App\Modules\Communication\Enums\EmailDeliveryStatus;
use App\Modules\Communication\Enums\EmailDeliveryType;
use App\Modules\Communication\Jobs\SendTemplatedEmailJob;
use App\Modules\Communication\Mail\TemplatedMail;
use App\Modules\Communication\Models\EmailDelivery;
use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\EmailTemplatePlaceholder;
use App\Modules\Communication\Models\EmailTemplateVersion;
use App\Modules\Communication\Services\EmailTemplateRenderer;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Support\Facades\Mail;

function deliveryForTemplatedJob(
    EmailDeliveryStatus $status = EmailDeliveryStatus::Queued,
    int $attempts = 0,
): EmailDelivery {
    $user = User::query()->create([
        'email' => fake()->unique()->safeEmail(),
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    $template = EmailTemplate::query()->create([
        'key' => 'auth.registration.verify',
        'name' => 'Registrierung bestätigen',
        'is_active' => true,
        'draft_subject' => 'Hallo {{ first_name }}',
        'draft_html' => '<p>Entwurf</p>',
        'updated_by_user_id' => $user->id,
    ]);

    foreach ([
        [
            'key' => 'verification_url',
            'label' => 'Verifikationslink',
            'required' => true,
        ],
        [
            'key' => 'expires_at',
            'label' => 'Ablaufzeitpunkt',
            'required' => true,
        ],
        [
            'key' => 'first_name',
            'label' => 'Vorname',
            'required' => false,
        ],
    ] as $placeholder) {
        EmailTemplatePlaceholder::query()->create([
            'email_template_id' => $template->id,
            'key' => $placeholder['key'],
            'label' => $placeholder['label'],
            'description' => $placeholder['label'],
            'example_value' => null,
            'is_required' => $placeholder['required'],
            'is_active' => true,
        ]);
    }

    $version = EmailTemplateVersion::query()->create([
        'email_template_id' => $template->id,
        'version' => 1,
        'subject' => 'Hallo {{ first_name }}',
        'html' => <<<'HTML'
<p>Hallo {{ first_name }}</p>
<a href="{{ verification_url }}">Bestätigen</a>
<p>Gültig bis {{ expires_at }}</p>
HTML,
        'published_by_user_id' => $user->id,
        'published_at' => now(),
    ]);

    return EmailDelivery::query()->create([
        'template_version_id' => $version->id,
        'sender_user_id' => null,
        'recipient_email' => 'recipient@example.test',
        'subject' => $version->subject,
        'delivery_type' => EmailDeliveryType::System,
        'status' => $status,
        'attempts' => $attempts,
        'queued_at' => now(),
        'sent_at' => $status === EmailDeliveryStatus::Sent
            ? now()
            : null,
    ]);
}

it('uses three attempts with approximately three minute backoff', function () {
    $job = new SendTemplatedEmailJob(
        emailDeliveryId: 1,
        values: [],
    );

    expect($job)
        ->toBeInstanceOf(ShouldBeEncrypted::class)
        ->and($job->tries)
        ->toBe(3)
        ->and($job->backoff())
        ->toBe([
            180,
            180,
        ]);
});

it('sends a rendered templated email and marks delivery as sent', function () {
    Mail::fake();

    $delivery = deliveryForTemplatedJob();

    $job = new SendTemplatedEmailJob(
        emailDeliveryId: $delivery->id,
        values: [
            'first_name' => 'Erika',
            'verification_url' => 'https://example.test/verify?a=1&b=2',
            'expires_at' => '04.09.2026 12:00',
        ],
    );

    $job->handle(
        app(EmailTemplateRenderer::class)
    );

    Mail::assertSent(
        TemplatedMail::class,
        fn (TemplatedMail $mail): bool => $mail->hasTo('recipient@example.test')
            && $mail->subjectLine === 'Hallo Erika'
            && str_contains(
                $mail->htmlContent,
                'https://example.test/verify?a=1&amp;b=2'
            )
    );

    $delivery->refresh();

    expect($delivery->status)
        ->toBe(EmailDeliveryStatus::Sent)
        ->and($delivery->subject)
        ->toBe('Hallo Erika')
        ->and($delivery->attempts)
        ->toBe(1)
        ->and($delivery->sent_at)
        ->not->toBeNull()
        ->and($delivery->failed_at)
        ->toBeNull()
        ->and($delivery->last_error_class)
        ->toBeNull();
});

it('does not send an already sent delivery again', function () {
    Mail::fake();

    $delivery = deliveryForTemplatedJob(
        status: EmailDeliveryStatus::Sent,
        attempts: 1,
    );

    $job = new SendTemplatedEmailJob(
        emailDeliveryId: $delivery->id,
        values: [
            'first_name' => 'Erika',
            'verification_url' => 'https://example.test/verify',
            'expires_at' => '04.09.2026 12:00',
        ],
    );

    $job->handle(
        app(EmailTemplateRenderer::class)
    );

    Mail::assertNothingSent();

    $delivery->refresh();

    expect($delivery->attempts)
        ->toBe(1)
        ->and($delivery->status)
        ->toBe(EmailDeliveryStatus::Sent);
});

it('marks a delivery as failed after final job failure', function () {
    $delivery = deliveryForTemplatedJob(
        attempts: 3,
    );

    $job = new SendTemplatedEmailJob(
        emailDeliveryId: $delivery->id,
        values: [],
    );

    $job->failed(
        new RuntimeException('SMTP unavailable')
    );

    $delivery->refresh();

    expect($delivery->status)
        ->toBe(EmailDeliveryStatus::Failed)
        ->and($delivery->failed_at)
        ->not->toBeNull()
        ->and($delivery->last_error_class)
        ->toBe(RuntimeException::class)
        ->and($delivery->attempts)
        ->toBe(3);
});
