<?php

use App\Modules\Identity\Exceptions\RegistrationVerificationEmailUnavailable;
use App\Modules\Communication\Models\EmailDelivery;
use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\EmailTemplateVersion;
use App\Modules\Identity\Actions\Registration\StartRegistrationWorkflowAction;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\RegistrationRequest;
use App\Modules\Identity\Models\User;
use Database\Seeders\RegistrationVerificationEmailTemplateSeeder;
use Illuminate\Support\Facades\Queue;

function prepareVerificationTemplateForSentAtWorkflowTest(): void
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

it('records the time when the initial verification email is prepared successfully', function () {
    Queue::fake();

    prepareVerificationTemplateForSentAtWorkflowTest();

    $before = now();

    app(StartRegistrationWorkflowAction::class)->execute(
        firstName: 'Erika',
        lastName: 'Mustermann',
        birthDate: '1990-05-10',
        email: 'erika@example.test',
        password: 'Sicher123!',
        privacyAccepted: true,
        privacyNoticeVersion: '2026-09-01T21:04:00Z',
        consentedAt: now(),
    );

    $registrationRequest = RegistrationRequest::query()
        ->sole();

    expect($registrationRequest->verification_sent_at)
        ->not->toBeNull()
        ->and(
            $registrationRequest
                ->verification_sent_at
                ->timestamp
        )
        ->toBeGreaterThanOrEqual(
            $before->timestamp
        )
        ->and(EmailDelivery::query()->count())
        ->toBe(1);
});

it('does not record a verification send time when email preparation fails', function () {
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
            privacyNoticeVersion: '2026-09-01T21:04:00Z',
            consentedAt: now(),
        )
    )->toThrow(
        RegistrationVerificationEmailUnavailable::class
    );

    $registrationRequest = RegistrationRequest::query()
        ->sole();

    expect($registrationRequest->verification_sent_at)
        ->toBeNull()
        ->and(EmailDelivery::query()->count())
        ->toBe(0);
});