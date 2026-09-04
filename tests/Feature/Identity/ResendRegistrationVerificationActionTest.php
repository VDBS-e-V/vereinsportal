<?php

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Communication\Exceptions\EmailTemplateUnavailable;
use App\Modules\Communication\Models\EmailDelivery;
use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\EmailTemplateVersion;
use App\Modules\Identity\Actions\Registration\ResendRegistrationVerificationAction;
use App\Modules\Identity\Enums\RegistrationRequestStatus;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Exceptions\RegistrationVerificationCannotBeResent;
use App\Modules\Identity\Models\RegistrationRequest;
use App\Modules\Identity\Models\User;
use Database\Seeders\RegistrationVerificationEmailTemplateSeeder;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

function prepareVerificationTemplateForResendActionTest(): void
{
    test()->seed(
        RegistrationVerificationEmailTemplateSeeder::class
    );

    $template = EmailTemplate::query()
        ->where('key', 'auth.registration.verify')
        ->sole();

    $publisher = User::query()->create([
        'email' => fake()->unique()->safeEmail(),
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    EmailTemplateVersion::query()->create([
        'email_template_id' => $template->id,
        'version' => 1,
        'subject' => 'Registrierung bestätigen',
        'html' => <<<'HTML'
<p>
    <a href="{{ verification_url }}">
        Registrierung bestätigen
    </a>
</p>
<p>Gültig bis {{ expires_at }}</p>
HTML,
        'published_by_user_id' => $publisher->id,
        'published_at' => now(),
    ]);

    $template->update([
        'is_active' => true,
    ]);
}

function makeRegistrationRequestForResend(
    array $attributes = [],
): RegistrationRequest {
    return RegistrationRequest::query()->create(
        array_merge(
            [
                'public_id' => (string) Str::ulid(),
                'first_name' => 'Erika',
                'last_name' => 'Mustermann',
                'birth_date' => '1990-05-10',
                'email' => 'erika@example.test',
                'password' => 'hashed-password',
                'privacy_notice_version' => '2026-09-01T21:04:00Z',
                'consented_at' => now(),
                'verification_recipient_email' => 'erika@example.test',
                'verification_version' => 1,
                'verification_expires_at' => now()->subHour(),
                'verification_sent_at' => now()->subMinutes(2),
                'expires_at' => now()->addDays(5),
                'status' => RegistrationRequestStatus::PendingVerification,
            ],
            $attributes,
        )
    );
}

it('resends verification with a new version and validity window', function () {
    Queue::fake();

    prepareVerificationTemplateForResendActionTest();

    $registrationRequest =
        makeRegistrationRequestForResend();

    $before = now();

    app(
        ResendRegistrationVerificationAction::class
    )->execute(
        publicId: $registrationRequest->public_id,
        ipAddress: '127.0.0.1',
        userAgent: 'Pest',
    );

    $registrationRequest->refresh();

    expect($registrationRequest->verification_version)
        ->toBe(2)
        ->and(
            $registrationRequest
                ->verification_expires_at
                ->timestamp
        )
        ->toBeGreaterThanOrEqual(
            $before->copy()->addDays(3)->timestamp - 1
        )
        ->and(
            $registrationRequest
                ->verification_sent_at
        )
        ->not->toBeNull()
        ->and(EmailDelivery::query()->count())
        ->toBe(1);

    $auditEvent = AuditEvent::query()
        ->where(
            'event_key',
            AuditEventCatalog::AUTH_VERIFICATION_RESENT,
        )
        ->sole();

    expect($auditEvent->subject_type)
        ->toBe('registration_request')
        ->and($auditEvent->subject_id)
        ->toBe($registrationRequest->id)
        ->and($auditEvent->new_values)
        ->toBeNull();
});

it('rate limits another resend within one minute', function () {
    prepareVerificationTemplateForResendActionTest();

    $registrationRequest =
        makeRegistrationRequestForResend([
            'verification_sent_at' => now()->subSeconds(30),
        ]);

    expect(
        fn () => app(
            ResendRegistrationVerificationAction::class
        )->execute(
            publicId: $registrationRequest->public_id,
        )
    )->toThrow(
        RegistrationVerificationCannotBeResent::class
    );

    $registrationRequest->refresh();

    expect($registrationRequest->verification_version)
        ->toBe(1)
        ->and(EmailDelivery::query()->count())
        ->toBe(0)
        ->and(
            AuditEvent::query()
                ->where(
                    'event_key',
                    AuditEventCatalog::AUTH_VERIFICATION_RESENT,
                )
                ->count()
        )
        ->toBe(0);
});

it('caps the renewed verification validity at the registration expiry', function () {
    Queue::fake();

    prepareVerificationTemplateForResendActionTest();

    $overallExpiry = now()->addHours(12);

    $registrationRequest =
        makeRegistrationRequestForResend([
            'expires_at' => $overallExpiry,
        ]);

    app(
        ResendRegistrationVerificationAction::class
    )->execute(
        publicId: $registrationRequest->public_id,
    );

    $registrationRequest->refresh();

    expect(
        $registrationRequest
            ->verification_expires_at
            ->timestamp
    )->toBe(
        $overallExpiry->timestamp
    );
});

it('does not resurrect an expired registration request', function () {
    prepareVerificationTemplateForResendActionTest();

    $registrationRequest =
        makeRegistrationRequestForResend([
            'expires_at' => now()->subSecond(),
        ]);

    expect(
        fn () => app(
            ResendRegistrationVerificationAction::class
        )->execute(
            publicId: $registrationRequest->public_id,
        )
    )->toThrow(
        RegistrationVerificationCannotBeResent::class
    );

    $registrationRequest->refresh();

    expect($registrationRequest->verification_version)
        ->toBe(1)
        ->and(EmailDelivery::query()->count())
        ->toBe(0);
});

it('rolls the new verification state back when email preparation fails', function () {
    $registrationRequest =
        makeRegistrationRequestForResend();

    $originalExpiry =
        $registrationRequest
            ->verification_expires_at
            ->timestamp;

    expect(
        fn () => app(
            ResendRegistrationVerificationAction::class
        )->execute(
            publicId: $registrationRequest->public_id,
        )
    )->toThrow(
        EmailTemplateUnavailable::class
    );

    $registrationRequest->refresh();

    expect($registrationRequest->verification_version)
        ->toBe(1)
        ->and(
            $registrationRequest
                ->verification_expires_at
                ->timestamp
        )
        ->toBe($originalExpiry)
        ->and(
            $registrationRequest
                ->verification_sent_at
                ->timestamp
        )
        ->toBeLessThanOrEqual(
            now()->subMinute()->timestamp
        )
        ->and(EmailDelivery::query()->count())
        ->toBe(0)
        ->and(
            AuditEvent::query()
                ->where(
                    'event_key',
                    AuditEventCatalog::AUTH_VERIFICATION_RESENT,
                )
                ->count()
        )
        ->toBe(0);
});
