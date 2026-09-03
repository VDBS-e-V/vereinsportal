<?php

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Communication\Models\EmailDelivery;
use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\EmailTemplateVersion;
use App\Modules\Identity\Enums\RegistrationRequestStatus;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\RegistrationRequest;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\RegistrationVerificationUrl;
use Database\Seeders\RegistrationVerificationEmailTemplateSeeder;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Livewire\Volt\Volt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function prepareVerificationTemplateForStatusPageTest(): void
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

function makeRegistrationRequestForStatusPage(
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
                'privacy_notice_version' =>
                    '2026-09-01T21:04:00Z',
                'consented_at' => now(),
                'verification_recipient_email' =>
                    'erika@example.test',
                'verification_version' => 1,
                'verification_expires_at' =>
                    now()->addDays(3),
                'verification_sent_at' => now(),
                'expires_at' => now()->addDays(7),
                'status' =>
                    RegistrationRequestStatus::PendingVerification,
            ],
            $attributes,
        )
    );
}

it('shows the pending registration verification status on the my domain', function () {
    $registrationRequest =
        makeRegistrationRequestForStatusPage();

    $this
        ->get(
            'http://my.vdb.test/registrierung/status/'
            .$registrationRequest->public_id
        )
        ->assertOk()
        ->assertSee('Registrierung bestätigen')
        ->assertSee('erika@example.test')
        ->assertSee(
            'Bitte öffnen Sie den Link in der E-Mail'
        );
});

it('shows when no verification email has been prepared yet', function () {
    $registrationRequest =
        makeRegistrationRequestForStatusPage([
            'verification_sent_at' => null,
        ]);

    $this
        ->get(
            'http://my.vdb.test/registrierung/status/'
            .$registrationRequest->public_id
        )
        ->assertOk()
        ->assertSee(
            'Die Bestätigungs-E-Mail konnte bislang'
        );
});

it('does not expose an expired registration status', function () {
    $registrationRequest =
        makeRegistrationRequestForStatusPage([
            'expires_at' => now()->subSecond(),
        ]);

    $this
        ->get(
            'http://my.vdb.test/registrierung/status/'
            .$registrationRequest->public_id
        )
        ->assertNotFound();
});

it('does not expose the registration status on another module domain', function () {
    $registrationRequest =
        makeRegistrationRequestForStatusPage();

    $this
        ->get(
            'http://verwaltung.vdb.test/registrierung/status/'
            .$registrationRequest->public_id
        )
        ->assertNotFound();
});

it('does not reveal whether an unknown registration exists', function () {
    $this
        ->get(
            'http://my.vdb.test/registrierung/status/'
            .Str::ulid()
        )
        ->assertNotFound();
});

it('resends the verification email from the registration status page', function () {
    Queue::fake();

    prepareVerificationTemplateForStatusPageTest();

    $registrationRequest =
        makeRegistrationRequestForStatusPage([
            'verification_sent_at' =>
                now()->subMinutes(2),
        ]);

    Volt::test(
        'identity.registration-status',
        [
            'publicId' =>
                $registrationRequest->public_id,
        ],
    )
        ->call('resend')
        ->assertHasNoErrors()
        ->assertSet('resent', true)
        ->assertSet('mailPrepared', true);

    $registrationRequest->refresh();

    expect($registrationRequest->verification_version)
        ->toBe(2)
        ->and(EmailDelivery::query()->count())
        ->toBe(1)
        ->and(
            AuditEvent::query()
                ->where(
                    'event_key',
                    AuditEventCatalog::AUTH_VERIFICATION_RESENT,
                )
                ->count()
        )
        ->toBe(1);
});

it('invalidates the previous verification link after a successful resend', function () {
    Queue::fake();

    prepareVerificationTemplateForStatusPageTest();

    $registrationRequest =
        makeRegistrationRequestForStatusPage([
            'verification_sent_at' =>
                now()->subMinutes(2),
        ]);

    $oldUrl = app(RegistrationVerificationUrl::class)
        ->create($registrationRequest);

    Volt::test(
        'identity.registration-status',
        [
            'publicId' =>
                $registrationRequest->public_id,
        ],
    )
        ->call('resend')
        ->assertHasNoErrors()
        ->assertSet('resent', true);

    $registrationRequest->refresh();

    expect($registrationRequest->verification_version)
        ->toBe(2)
        ->and(
            URL::hasValidSignature(
                Request::create($oldUrl)
            )
        )
        ->toBeTrue();

    $this
        ->get($oldUrl)
        ->assertUnprocessable();

    expect(
        RegistrationRequest::query()
            ->whereKey($registrationRequest->id)
            ->exists()
    )
        ->toBeTrue()
        ->and(Person::query()->count())
        ->toBe(0)
        ->and(
            User::query()
                ->where('email', 'erika@example.test')
                ->count()
        )
        ->toBe(0);

    $user = app(
        \App\Modules\Identity\Actions\Registration\CompleteRegistrationAction::class
    )->execute(
        publicId: $registrationRequest->public_id,
        version: 2,
    );

    expect($user->email)
        ->toBe('erika@example.test')
        ->and(
            RegistrationRequest::query()
                ->whereKey($registrationRequest->id)
                ->exists()
        )
        ->toBeFalse()
        ->and(
            Person::query()
                ->where('email', 'erika@example.test')
                ->count()
        )
        ->toBe(1)
        ->and(
            User::query()
                ->where('email', 'erika@example.test')
                ->count()
        )
        ->toBe(1);
});

it('caps the renewed verification expiry at the overall registration expiry', function () {
    Queue::fake();

    prepareVerificationTemplateForStatusPageTest();

    $overallExpiry = now()
        ->addHours(2)
        ->startOfSecond();

    $registrationRequest =
        makeRegistrationRequestForStatusPage([
            'verification_sent_at' =>
                now()->subMinutes(2),
            'verification_expires_at' =>
                now()->addMinutes(30),
            'expires_at' =>
                $overallExpiry,
        ]);

    Volt::test(
        'identity.registration-status',
        [
            'publicId' =>
                $registrationRequest->public_id,
        ],
    )
        ->call('resend')
        ->assertHasNoErrors()
        ->assertSet('resent', true);

    $registrationRequest->refresh();

    expect(
        $registrationRequest
            ->verification_expires_at
            ->equalTo(
                $registrationRequest->expires_at
            )
    )->toBeTrue();
});

it('rolls resend state back when the verification email cannot be prepared', function () {
    $registrationRequest =
        makeRegistrationRequestForStatusPage([
            'verification_sent_at' =>
                now()->subMinutes(2),
        ]);

    $previousVersion =
        $registrationRequest->verification_version;

    $previousVerificationExpiry =
        $registrationRequest
            ->verification_expires_at
            ->copy();

    $previousSentAt =
        $registrationRequest
            ->verification_sent_at
            ->copy();

    Volt::test(
        'identity.registration-status',
        [
            'publicId' =>
                $registrationRequest->public_id,
        ],
    )
        ->call('resend')
        ->assertHasErrors([
            'resend',
        ])
        ->assertSet('resent', false);

    $registrationRequest->refresh();

    expect($registrationRequest->verification_version)
        ->toBe($previousVersion)
        ->and(
            $registrationRequest
                ->verification_expires_at
                ->equalTo(
                    $previousVerificationExpiry
                )
        )
        ->toBeTrue()
        ->and(
            $registrationRequest
                ->verification_sent_at
                ->equalTo($previousSentAt)
        )
        ->toBeTrue()
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

it('shows the resend rate limit on the registration status page', function () {
    $registrationRequest =
        makeRegistrationRequestForStatusPage([
            'verification_sent_at' =>
                now()->subSeconds(30),
        ]);

    Volt::test(
        'identity.registration-status',
        [
            'publicId' =>
                $registrationRequest->public_id,
        ],
    )
        ->call('resend')
        ->assertHasErrors([
            'resend',
        ])
        ->assertSet('resent', false);

    $registrationRequest->refresh();

    expect($registrationRequest->verification_version)
        ->toBe(1)
        ->and(EmailDelivery::query()->count())
        ->toBe(0);
});

it('can resend from the status page when the initial email was never prepared', function () {
    Queue::fake();

    prepareVerificationTemplateForStatusPageTest();

    $registrationRequest =
        makeRegistrationRequestForStatusPage([
            'verification_sent_at' => null,
        ]);

    Volt::test(
        'identity.registration-status',
        [
            'publicId' =>
                $registrationRequest->public_id,
        ],
    )
        ->call('resend')
        ->assertHasNoErrors()
        ->assertSet('resent', true);

    $registrationRequest->refresh();

    expect($registrationRequest->verification_version)
        ->toBe(2)
        ->and(
            $registrationRequest->verification_sent_at
        )
        ->not->toBeNull()
        ->and(EmailDelivery::query()->count())
        ->toBe(1);
});