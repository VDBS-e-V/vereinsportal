<?php

use App\Modules\Communication\Enums\EmailDeliveryStatus;
use App\Modules\Communication\Enums\EmailDeliveryType;
use App\Modules\Identity\Exceptions\RegistrationVerificationEmailUnavailable;
use App\Modules\Communication\Jobs\SendTemplatedEmailJob;
use App\Modules\Communication\Models\EmailDelivery;
use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\EmailTemplateVersion;
use App\Modules\Identity\Actions\Registration\StartRegistrationWorkflowAction;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\RegistrationRequest;
use App\Modules\Identity\Models\User;
use Database\Seeders\RegistrationVerificationEmailTemplateSeeder;
use Illuminate\Support\Facades\Queue;

function prepareActiveRegistrationVerificationTemplate(): void
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
<p>Hallo {{ first_name }}</p>
<p>Bitte bestätigen Sie Ihre Registrierung:</p>
<p><a href="{{ verification_url }}">Registrierung bestätigen</a></p>
<p>Der Link ist gültig bis {{ expires_at }}.</p>
HTML,
        'published_by_user_id' => $publisher->id,
        'published_at' => now(),
    ]);

    $template->update([
        'is_active' => true,
    ]);
}

it('starts registration and queues its verification email', function () {
    Queue::fake();

    prepareActiveRegistrationVerificationTemplate();

    $registrationRequest = app(
        StartRegistrationWorkflowAction::class
    )->execute(
        firstName: 'Erika',
        lastName: 'Mustermann',
        birthDate: '1990-05-10',
        email: 'Erika@example.test',
        password: 'Sicher123!',
        privacyAccepted: true,
        privacyNoticeVersion: 'privacy-v1',
        consentedAt: now(),
    );

    expect($registrationRequest)
        ->toBeInstanceOf(RegistrationRequest::class)
        ->and($registrationRequest->email)
        ->toBe('erika@example.test');

    $delivery = EmailDelivery::query()->sole();

    expect($delivery->recipient_email)
        ->toBe('erika@example.test')
        ->and($delivery->delivery_type)
        ->toBe(EmailDeliveryType::System)
        ->and($delivery->status)
        ->toBe(EmailDeliveryStatus::Queued)
        ->and($delivery->attempts)
        ->toBe(0);

    Queue::assertPushed(
        SendTemplatedEmailJob::class,
        function (
            SendTemplatedEmailJob $job
        ) use ($delivery): bool {
            return $job->emailDeliveryId === $delivery->id
                && $job->afterCommit === true;
        }
    );
});

it('keeps the registration request when the verification email cannot be queued', function () {
    /*
     * Absichtlich kein Template seeden.
     *
     * Damit simulieren wir eine fehlerhafte
     * Communication-Konfiguration.
     */
    expect(
        fn () => app(
            StartRegistrationWorkflowAction::class
        )->execute(
            firstName: 'Erika',
            lastName: 'Mustermann',
            birthDate: '1990-05-10',
            email: 'erika@example.test',
            password: 'Sicher123!',
            privacyAccepted: true,
            privacyNoticeVersion: 'privacy-v1',
            consentedAt: now(),
        )
    )->toThrow(
        RegistrationVerificationEmailUnavailable::class
    );

    expect(
        RegistrationRequest::query()->count()
    )->toBe(1)
        ->and(
            RegistrationRequest::query()
                ->sole()
                ->email
        )
        ->toBe('erika@example.test')
        ->and(
            EmailDelivery::query()->count()
        )
        ->toBe(0);
});

it('exposes the retained registration public id when verification email preparation fails', function () {
    try {
        app(
            StartRegistrationWorkflowAction::class
        )->execute(
            firstName: 'Erika',
            lastName: 'Mustermann',
            birthDate: '1990-05-10',
            email: 'erika@example.test',
            password: 'Sicher123!',
            privacyAccepted: true,
            privacyNoticeVersion: '2026-09-01T21:04:00Z',
            consentedAt: now(),
        );

        test()->fail(
            'Expected registration verification email failure.'
        );
    } catch (
        RegistrationVerificationEmailUnavailable $exception
    ) {
        $registrationRequest = RegistrationRequest::query()
            ->sole();

        expect($exception->registrationPublicId)
            ->toBe($registrationRequest->public_id)
            ->and(
                $registrationRequest
                    ->verification_sent_at
            )
            ->toBeNull();
    }
});