<?php

use App\Modules\Identity\Enums\RegistrationRequestStatus;
use App\Modules\Identity\Models\RegistrationRequest;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

it('stores the last successful verification mail preparation time', function () {
    $sentAt = now()->subSeconds(10);

    $registrationRequest = RegistrationRequest::query()->create([
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
        'verification_expires_at' => now()->addDays(3),
        'verification_sent_at' => $sentAt,
        'expires_at' => now()->addDays(7),
        'status' => RegistrationRequestStatus::PendingVerification,
    ]);

    expect($registrationRequest->verification_sent_at)
        ->toBeInstanceOf(CarbonInterface::class)
        ->and(
            $registrationRequest
                ->verification_sent_at
                ->timestamp
        )
        ->toBe($sentAt->timestamp);
});

it('allows a registration request without a successful verification mail preparation yet', function () {
    $registrationRequest = RegistrationRequest::query()->create([
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
        'verification_expires_at' => now()->addDays(3),
        'expires_at' => now()->addDays(7),
        'status' => RegistrationRequestStatus::PendingVerification,
    ]);

    expect(
        $registrationRequest->verification_sent_at
    )->toBeNull();
});
