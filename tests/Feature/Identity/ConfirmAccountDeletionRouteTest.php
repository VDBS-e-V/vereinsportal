<?php

use App\Modules\Identity\Enums\AccountDeletionRequestStatus;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\AccountDeletionRequest;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\AccountDeletionConfirmationUrl;
use Illuminate\Support\Str;

it('confirms account deletion through the signed route', function () {
    $user = User::query()->create([
        'email' => 'route-delete@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 1,
    ]);

    $user->email_verified_at = now();
    $user->save();

    $deletionRequest = AccountDeletionRequest::query()->create([
        'public_id' => (string) Str::ulid(),
        'user_id' => $user->id,
        'status' => AccountDeletionRequestStatus::PendingConfirmation,
        'requested_at' => now(),
        'confirmation_sent_at' => now(),
    ]);

    $urlService = app(
        AccountDeletionConfirmationUrl::class
    );

    $url = $urlService->create(
        $deletionRequest,
        $urlService->expiresAt(),
    );

    $response = $this
        ->actingAs($user)
        ->get($url);

    $response->assertRedirect(
        route('my.login')
    );

    $this->assertGuest();

    expect($user->refresh()->status)
        ->toBe(UserStatus::PendingDeletion)
        ->and($user->remember_token)
        ->toBeNull();
});

it('rejects a manipulated account deletion confirmation link', function () {
    $user = User::query()->create([
        'email' => 'tampered-delete@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 1,
    ]);

    $user->email_verified_at = now();
    $user->save();

    $deletionRequest = AccountDeletionRequest::query()->create([
        'public_id' => (string) Str::ulid(),
        'user_id' => $user->id,
        'status' => AccountDeletionRequestStatus::PendingConfirmation,
        'requested_at' => now(),
        'confirmation_sent_at' => now(),
    ]);

    $urlService = app(
        AccountDeletionConfirmationUrl::class
    );

    $url = $urlService->create(
        $deletionRequest,
        $urlService->expiresAt(),
    );

    $tampered = str_replace(
        $deletionRequest->public_id,
        (string) Str::ulid(),
        $url,
    );

    $this->get($tampered)
        ->assertForbidden();

    expect($user->refresh()->status)
        ->toBe(UserStatus::Active);
});
