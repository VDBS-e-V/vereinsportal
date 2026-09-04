<?php

use App\Modules\Identity\Enums\AccountDeletionRequestStatus;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\AccountDeletionRequest;
use App\Modules\Identity\Models\AccountDeletionStopReason;
use App\Modules\Identity\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

it('has the account deletion foundation schema', function () {
    expect(
        Schema::hasColumns(
            'account_deletion_stop_reasons',
            [
                'id',
                'key',
                'label',
                'requires_comment',
                'is_active',
                'created_at',
                'updated_at',
            ],
        )
    )->toBeTrue();

    expect(
        Schema::hasColumns(
            'account_deletion_requests',
            [
                'id',
                'public_id',
                'user_id',
                'anonymous_user_ref',
                'status',
                'requested_at',
                'confirmation_sent_at',
                'confirmed_at',
                'revoke_until',
                'withdrawn_at',
                'stopped_at',
                'stopped_by_user_id',
                'stop_reason_id',
                'stop_comment',
                'completed_at',
                'created_at',
                'updated_at',
            ],
        )
    )->toBeTrue();
});

it('casts account deletion lifecycle values correctly', function () {
    $user = User::query()->create([
        'email' => 'deletion@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 1,
    ]);

    $request = AccountDeletionRequest::query()->create([
        'public_id' => (string) Str::ulid(),
        'user_id' => $user->id,
        'status' => AccountDeletionRequestStatus::PendingConfirmation,
        'requested_at' => now(),
        'confirmation_sent_at' => now(),
    ]);

    $request->refresh();

    expect($request->status)
        ->toBe(AccountDeletionRequestStatus::PendingConfirmation)
        ->and($request->requested_at)
        ->not->toBeNull()
        ->and($request->confirmation_sent_at)
        ->not->toBeNull()
        ->and($request->user?->is($user))
        ->toBeTrue();
});

it('stores administrative stop metadata', function () {
    $user = User::query()->create([
        'email' => 'stopped@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 1,
    ]);

    $administrator = User::query()->create([
        'email' => 'administrator@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 1,
    ]);

    $reason = AccountDeletionStopReason::query()->create([
        'key' => 'test_reason',
        'label' => 'Testgrund',
        'requires_comment' => true,
        'is_active' => true,
    ]);

    $request = AccountDeletionRequest::query()->create([
        'public_id' => (string) Str::ulid(),
        'user_id' => $user->id,
        'status' => AccountDeletionRequestStatus::Stopped,
        'requested_at' => now()->subDay(),
        'confirmation_sent_at' => now()->subDay(),
        'confirmed_at' => now()->subHours(12),
        'revoke_until' => now()->addDays(4),
        'stopped_at' => now(),
        'stopped_by_user_id' => $administrator->id,
        'stop_reason_id' => $reason->id,
        'stop_comment' => 'Administrativ gestoppt.',
    ]);

    expect($request->stopReason?->is($reason))
        ->toBeTrue()
        ->and($request->stoppedByUser?->is($administrator))
        ->toBeTrue()
        ->and($request->stop_comment)
        ->toBe('Administrativ gestoppt.');
});

it('enforces unique public deletion request ids', function () {
    $publicId = (string) Str::ulid();

    AccountDeletionRequest::query()->create([
        'public_id' => $publicId,
        'status' => AccountDeletionRequestStatus::PendingConfirmation,
        'requested_at' => now(),
        'confirmation_sent_at' => now(),
    ]);

    expect(fn () => AccountDeletionRequest::query()->create([
        'public_id' => $publicId,
        'status' => AccountDeletionRequestStatus::PendingConfirmation,
        'requested_at' => now(),
        'confirmation_sent_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('enforces unique deletion stop reason keys', function () {
    AccountDeletionStopReason::query()->create([
        'key' => 'duplicate_test',
        'label' => 'Erster Grund',
        'requires_comment' => false,
        'is_active' => true,
    ]);

    expect(fn () => AccountDeletionStopReason::query()->create([
        'key' => 'duplicate_test',
        'label' => 'Zweiter Grund',
        'requires_comment' => false,
        'is_active' => true,
    ]))->toThrow(QueryException::class);
});

it('allows a deletion request before its confirmation email is prepared', function () {
    $request = AccountDeletionRequest::query()->create([
        'public_id' => (string) Str::ulid(),
        'status' => AccountDeletionRequestStatus::PendingConfirmation,
        'requested_at' => now(),
        'confirmation_sent_at' => null,
    ]);

    expect($request->confirmation_sent_at)
        ->toBeNull();
});