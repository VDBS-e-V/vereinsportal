<?php

use App\Modules\Identity\Enums\RegistrationRequestStatus;
use App\Modules\Identity\Models\RegistrationRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

it('casts registration request lifecycle values correctly', function () {
    $request = RegistrationRequest::query()->create([
        'public_id' => (string) Str::ulid(),
        'first_name' => 'Erika',
        'last_name' => 'Mustermann',
        'birth_date' => '1990-01-15',
        'email' => 'erika@example.test',
        'password' => bcrypt('Sicher123!'),
        'privacy_notice_version' => '2026-08',
        'consented_at' => now(),
        'match_count' => 0,
        'verification_recipient_email' => 'erika@example.test',
        'verification_version' => 1,
        'verification_expires_at' => now()->addDays(3),
        'expires_at' => now()->addDays(7),
        'status' => RegistrationRequestStatus::PendingVerification,
    ]);

    expect($request->status)
        ->toBe(RegistrationRequestStatus::PendingVerification)
        ->and($request->birth_date)
        ->toBeInstanceOf(Carbon::class)
        ->and($request->consented_at)
        ->toBeInstanceOf(Carbon::class)
        ->and($request->verification_expires_at)
        ->toBeInstanceOf(Carbon::class)
        ->and($request->expires_at)
        ->toBeInstanceOf(Carbon::class);
});
