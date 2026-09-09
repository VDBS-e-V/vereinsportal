<?php

use App\Modules\Identity\Enums\AccountDeletionRequestStatus;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\AccountDeletionRequest;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\AccountDeletionWithdrawalUrl;
use Illuminate\Support\Str;

it('withdraws account deletion through the signed route without logging the user in', function () {
    $user = User::query()->create([
        'email' => 'withdraw-route@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::PendingDeletion,
        'session_version' => 3,
    ]);

    $user->email_verified_at = now();
    $user->remember_token = null;
    $user->save();

    $deletionRequest = AccountDeletionRequest::query()->create([
        'public_id' => (string) Str::ulid(),
        'user_id' => $user->id,
        'status' => AccountDeletionRequestStatus::PendingDeletion,
        'requested_at' => now()->subDay(),
        'confirmation_sent_at' => now()->subDay(),
        'confirmed_at' => now()->subHour(),
        'revoke_until' => now()->addDays(5),
    ]);

    $url = app(
        AccountDeletionWithdrawalUrl::class
    )->create($deletionRequest);

    $response = $this->get($url);

    $response->assertRedirect(route('my.login'));
    $this->assertGuest();

    expect($deletionRequest->refresh()->status)
        ->toBe(AccountDeletionRequestStatus::Withdrawn)
        ->and($user->refresh()->status)
        ->toBe(UserStatus::Active)
        ->and($user->session_version)
        ->toBe(3)
        ->and($user->remember_token)
        ->toBeNull();
});

it('rejects a manipulated account deletion withdrawal link', function () {
    $user = User::query()->create([
        'email' => 'withdraw-route-tampered@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::PendingDeletion,
        'session_version' => 3,
    ]);

    $user->email_verified_at = now();
    $user->remember_token = null;
    $user->save();

    $deletionRequest = AccountDeletionRequest::query()->create([
        'public_id' => (string) Str::ulid(),
        'user_id' => $user->id,
        'status' => AccountDeletionRequestStatus::PendingDeletion,
        'requested_at' => now()->subDay(),
        'confirmation_sent_at' => now()->subDay(),
        'confirmed_at' => now()->subHour(),
        'revoke_until' => now()->addDays(5),
    ]);

    $url = app(
        AccountDeletionWithdrawalUrl::class
    )->create($deletionRequest);

    $tampered = str_replace(
        $deletionRequest->public_id,
        (string) Str::ulid(),
        $url,
    );

    $this->get($tampered)->assertForbidden();

    expect($deletionRequest->refresh()->status)
        ->toBe(AccountDeletionRequestStatus::PendingDeletion)
        ->and($user->refresh()->status)
        ->toBe(UserStatus::PendingDeletion);
});
