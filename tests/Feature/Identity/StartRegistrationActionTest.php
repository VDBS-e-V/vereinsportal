<?php

use App\Modules\Identity\Actions\Registration\StartRegistrationAction;
use App\Modules\Identity\Enums\RegistrationRequestStatus;
use App\Modules\Identity\Exceptions\RegistrationCannotStart;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\RegistrationRequest;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Enums\UserStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

afterEach(function () {
    CarbonImmutable::setTestNow();
});

it('creates a pending registration request with normalized email and hashed password', function () {
    CarbonImmutable::setTestNow(
        CarbonImmutable::parse('2026-09-01 12:00:00', 'UTC')
    );

    $consentedAt = CarbonImmutable::parse(
        '2026-09-01 11:59:00',
        'UTC',
    );

    $request = app(StartRegistrationAction::class)->execute(
        firstName: 'Erika',
        lastName: 'Mustermann',
        birthDate: '1990-01-15',
        email: '  ERIKA@EXAMPLE.TEST  ',
        password: 'Sicher123!',
        privacyAccepted: true,
        privacyNoticeVersion: 'privacy-v1',
        consentedAt: $consentedAt,
    );

    expect($request->public_id)
        ->toHaveLength(26)
        ->and($request->email)
        ->toBe('erika@example.test')
        ->and($request->verification_recipient_email)
        ->toBe('erika@example.test')
        ->and($request->status)
        ->toBe(RegistrationRequestStatus::PendingVerification)
        ->and($request->verification_version)
        ->toBe(1)
        ->and($request->verification_expires_at->toDateTimeString())
        ->toBe('2026-09-04 12:00:00')
        ->and($request->expires_at->toDateTimeString())
        ->toBe('2026-09-08 12:00:00')
        ->and(Hash::check('Sicher123!', $request->getRawOriginal('password')))
        ->toBeTrue()
        ->and($request->getRawOriginal('password'))
        ->not->toBe('Sicher123!');
});

it('does not persist anything when a possible duplicate person is found', function () {
    Person::query()->create([
        'first_name' => 'Erika',
        'last_name' => 'Mustermann',
        'birth_date' => '1990-01-15',
        'email' => 'existing@example.test',
        'country_code' => 'DE',
    ]);

    expect(
        fn () => app(StartRegistrationAction::class)->execute(
            firstName: 'Erika',
            lastName: 'Mustermann',
            birthDate: '1990-01-15',
            email: 'new@example.test',
            password: 'Sicher123!',
            privacyAccepted: true,
            privacyNoticeVersion: 'privacy-v1',
            consentedAt: now(),
        )
    )->toThrow(RegistrationCannotStart::class);

    expect(RegistrationRequest::query()->count())
        ->toBe(0);
});

it('does not create a registration request for an already occupied email', function () {
    User::query()->create([
        'email' => 'occupied@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    expect(
        fn () => app(StartRegistrationAction::class)->execute(
            firstName: 'Completely',
            lastName: 'Different',
            birthDate: '1980-05-20',
            email: ' OCCUPIED@EXAMPLE.TEST ',
            password: 'Sicher123!',
            privacyAccepted: true,
            privacyNoticeVersion: 'privacy-v1',
            consentedAt: now(),
        )
    )->toThrow(RegistrationCannotStart::class);

    expect(RegistrationRequest::query()->count())
        ->toBe(0);
});

it('requires explicit privacy consent', function () {
    expect(
        fn () => app(StartRegistrationAction::class)->execute(
            firstName: 'Erika',
            lastName: 'Mustermann',
            birthDate: '1990-01-15',
            email: 'erika@example.test',
            password: 'Sicher123!',
            privacyAccepted: false,
            privacyNoticeVersion: 'privacy-v1',
            consentedAt: now(),
        )
    )->toThrow(ValidationException::class);

    expect(RegistrationRequest::query()->count())
        ->toBe(0);
});