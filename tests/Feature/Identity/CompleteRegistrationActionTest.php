<?php

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Actions\Registration\CompleteRegistrationAction;
use App\Modules\Identity\Actions\Registration\StartRegistrationAction;
use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Exceptions\RegistrationCannotComplete;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\PrivacyConsent;
use App\Modules\Identity\Models\RegistrationRequest;
use App\Modules\Identity\Models\RoleAssignment;
use App\Modules\Identity\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    CarbonImmutable::setTestNow(
        CarbonImmutable::parse('2026-09-01 12:00:00', 'UTC')
    );
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

function startCompletableRegistration(): RegistrationRequest
{
    return app(StartRegistrationAction::class)->execute(
        firstName: 'Erika',
        lastName: 'Mustermann',
        birthDate: '1990-01-15',
        email: 'erika@example.test',
        password: 'Sicher123!',
        privacyAccepted: true,
        privacyNoticeVersion: 'privacy-v1',
        consentedAt: now(),
    );
}

it('completes a verified registration atomically', function () {
    $registrationRequest = startCompletableRegistration();

    $user = app(CompleteRegistrationAction::class)->execute(
        publicId: $registrationRequest->public_id,
        version: 1,
        ipAddress: '127.0.0.1',
        userAgent: 'Pest',
    );

    expect($user->status)
        ->toBe(UserStatus::Active)
        ->and($user->email)
        ->toBe('erika@example.test')
        ->and($user->email_verified_at)
        ->not->toBeNull()
        ->and($user->person)
        ->not->toBeNull()
        ->and($user->person->email)
        ->toBe('erika@example.test')
        ->and(Hash::check('Sicher123!', $user->password))
        ->toBeTrue();

    expect(RegistrationRequest::query()->count())
        ->toBe(0)
        ->and(Person::query()->count())
        ->toBe(1)
        ->and(User::query()->count())
        ->toBe(1)
        ->and(PrivacyConsent::query()->count())
        ->toBe(1)
        ->and(RoleAssignment::query()->count())
        ->toBe(1);

    $consent = PrivacyConsent::query()->sole();

    expect($consent->context)
        ->toBe('registration')
        ->and($consent->notice_version)
        ->toBe('privacy-v1')
        ->and($consent->accepted)
        ->toBeTrue();

    $assignment = RoleAssignment::query()
        ->with('role')
        ->sole();

    expect($assignment->role->key)
        ->toBe(RoleKey::Guest->value);

    expect(
        AuditEvent::query()
            ->pluck('event_key')
            ->all()
    )->toContain(
        AuditEventCatalog::ROLE_AUTOMATIC_ASSIGNED,
        AuditEventCatalog::AUTH_EMAIL_VERIFIED,
        AuditEventCatalog::ACCOUNT_REGISTERED,
    );

    $registrationAudit = AuditEvent::query()
        ->where(
            'event_key',
            AuditEventCatalog::ACCOUNT_REGISTERED
        )
        ->sole();

    expect($registrationAudit->actor_user_id)
        ->toBe($user->id)
        ->and($registrationAudit->subject_id)
        ->toBe($user->id)
        ->and($registrationAudit->new_values)
        ->toBe([
            'linkage_type' => 'new_person',
        ])
        ->and($registrationAudit->ip_address)
        ->toBe('127.0.0.1')
        ->and($registrationAudit->user_agent)
        ->toBe('Pest');
});

it('blocks completion when a duplicate appeared after registration started', function () {
    $registrationRequest = startCompletableRegistration();

    Person::query()->create([
        'first_name' => 'Erika',
        'last_name' => 'Mustermann',
        'birth_date' => '1990-01-15',
        'email' => 'different@example.test',
        'country_code' => 'DE',
    ]);

    expect(
        fn () => app(CompleteRegistrationAction::class)->execute(
            publicId: $registrationRequest->public_id,
            version: 1,
        )
    )->toThrow(RegistrationCannotComplete::class);

    expect(RegistrationRequest::query()->count())
        ->toBe(1)
        ->and(Person::query()->count())
        ->toBe(1)
        ->and(User::query()->count())
        ->toBe(0)
        ->and(PrivacyConsent::query()->count())
        ->toBe(0)
        ->and(RoleAssignment::query()->count())
        ->toBe(0)
        ->and(AuditEvent::query()->count())
        ->toBe(0);
});

it('blocks completion when the email became occupied', function () {
    $registrationRequest = startCompletableRegistration();

    User::query()->create([
        'email' => 'erika@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    expect(
        fn () => app(CompleteRegistrationAction::class)->execute(
            publicId: $registrationRequest->public_id,
            version: 1,
        )
    )->toThrow(RegistrationCannotComplete::class);

    expect(RegistrationRequest::query()->count())
        ->toBe(1)
        ->and(Person::query()->count())
        ->toBe(0);
});

it('rejects an expired registration request', function () {
    $registrationRequest = startCompletableRegistration();

    CarbonImmutable::setTestNow(
        CarbonImmutable::parse('2026-09-09 12:00:00', 'UTC')
    );

    expect(
        fn () => app(CompleteRegistrationAction::class)->execute(
            publicId: $registrationRequest->public_id,
            version: 1,
        )
    )->toThrow(RegistrationCannotComplete::class);

    expect(RegistrationRequest::query()->count())
        ->toBe(1)
        ->and(Person::query()->count())
        ->toBe(0)
        ->and(User::query()->count())
        ->toBe(0);
});

it('rejects a stale verification version', function () {
    $registrationRequest = startCompletableRegistration();

    expect(
        fn () => app(CompleteRegistrationAction::class)->execute(
            publicId: $registrationRequest->public_id,
            version: 2,
        )
    )->toThrow(RegistrationCannotComplete::class);

    expect(RegistrationRequest::query()->count())
        ->toBe(1)
        ->and(Person::query()->count())
        ->toBe(0)
        ->and(User::query()->count())
        ->toBe(0);
});