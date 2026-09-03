<?php

use App\Modules\Identity\Enums\RegistrationRequestStatus;
use App\Modules\Identity\Models\RegistrationRequest;
use App\Modules\Identity\Support\RegistrationVerificationUrl;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

afterEach(function () {
    CarbonImmutable::setTestNow();
});

function verificationRequest(): RegistrationRequest
{
    return RegistrationRequest::query()->create([
        'public_id' => (string) \Illuminate\Support\Str::ulid(),
        'first_name' => 'Erika',
        'last_name' => 'Mustermann',
        'birth_date' => '1990-01-15',
        'email' => 'erika@example.test',
        'password' => Hash::make('Sicher123!'),
        'privacy_notice_version' => 'privacy-v1',
        'consented_at' => now(),
        'verification_recipient_email' => 'erika@example.test',
        'verification_version' => 1,
        'verification_expires_at' => now()->addDays(3),
        'expires_at' => now()->addDays(7),
        'status' => RegistrationRequestStatus::PendingVerification,
    ]);
}

it('creates a valid signed registration verification url', function () {
    CarbonImmutable::setTestNow(
        CarbonImmutable::parse('2026-09-01 12:00:00', 'UTC')
    );

    $registrationRequest = verificationRequest();

    $url = app(RegistrationVerificationUrl::class)
        ->create($registrationRequest);

    $request = Request::create($url, 'GET');

    expect(URL::hasValidSignature($request))
        ->toBeTrue()
        ->and($url)
        ->toContain($registrationRequest->public_id)
        ->toContain('/1?');
});

it('rejects a tampered registration verification url', function () {
    $registrationRequest = verificationRequest();

    $url = app(RegistrationVerificationUrl::class)
        ->create($registrationRequest);

    $tamperedUrl = str_replace(
        '/1?',
        '/2?',
        $url,
    );

    $this->get($tamperedUrl)
        ->assertForbidden();
});

it('rejects an expired registration verification url', function () {
    CarbonImmutable::setTestNow(
        CarbonImmutable::parse('2026-09-01 12:00:00', 'UTC')
    );

    $registrationRequest = verificationRequest();

    $url = app(RegistrationVerificationUrl::class)
        ->create($registrationRequest);

    CarbonImmutable::setTestNow(
        CarbonImmutable::parse('2026-09-04 12:00:01', 'UTC')
    );

    $this->get($url)
        ->assertForbidden();
});