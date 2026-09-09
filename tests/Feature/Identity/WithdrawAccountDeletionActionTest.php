<?php

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Actions\AccountDeletion\WithdrawAccountDeletionAction;
use App\Modules\Identity\Enums\AccountDeletionRequestStatus;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Exceptions\AccountDeletionCannotWithdraw;
use App\Modules\Identity\Models\AccountDeletionRequest;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Str;

function makeWithdrawDeletionUser(
    UserStatus $status = UserStatus::PendingDeletion,
): User {
    $user = User::query()->create([
        'email' => 'withdraw-delete-'.Str::lower((string) Str::ulid()).'@example.test',
        'password' => 'Sicher123!',
        'status' => $status,
        'session_version' => 7,
    ]);

    $user->email_verified_at = now();
    $user->remember_token = null;
    $user->save();

    return $user->refresh();
}

function makeWithdrawDeletionRequest(
    User $user,
    mixed $revokeUntil = null,
): AccountDeletionRequest {
    return AccountDeletionRequest::query()->create([
        'public_id' => (string) Str::ulid(),
        'user_id' => $user->id,
        'status' => AccountDeletionRequestStatus::PendingDeletion,
        'requested_at' => now()->subDay(),
        'confirmation_sent_at' => now()->subDay(),
        'confirmed_at' => now()->subHour(),
        'revoke_until' => $revokeUntil ?? now()->addDays(5),
    ]);
}

it('withdraws a pending account deletion atomically', function () {
    $user = makeWithdrawDeletionUser();
    $request = makeWithdrawDeletionRequest($user);

    $withdrawn = app(
        WithdrawAccountDeletionAction::class
    )->execute(
        publicId: $request->public_id,
        ipAddress: '127.0.0.1',
        userAgent: 'Pest',
    );

    $user->refresh();

    expect($withdrawn->status)
        ->toBe(AccountDeletionRequestStatus::Withdrawn)
        ->and($withdrawn->withdrawn_at)
        ->not->toBeNull()
        ->and($user->status)
        ->toBe(UserStatus::Active)
        ->and($user->session_version)
        ->toBe(7)
        ->and($user->remember_token)
        ->toBeNull();

    $audit = AuditEvent::query()
        ->where(
            'event_key',
            AuditEventCatalog::ACCOUNT_DELETION_WITHDRAWN,
        )
        ->sole();

    expect($audit->actor_user_id)
        ->toBe($user->id)
        ->and($audit->subject_id)
        ->toBe($user->id)
        ->and($audit->new_values)
        ->toHaveKey('withdrawn_at');
});

it('allows withdrawal exactly at revoke_until', function () {
    $boundary = now()->addHour()->startOfSecond();
    $user = makeWithdrawDeletionUser();
    $request = makeWithdrawDeletionRequest($user, $boundary);

    $this->travelTo($boundary);

    $withdrawn = app(
        WithdrawAccountDeletionAction::class
    )->execute($request->public_id);

    expect($withdrawn->status)
        ->toBe(AccountDeletionRequestStatus::Withdrawn)
        ->and($user->refresh()->status)
        ->toBe(UserStatus::Active);
});

it('rejects withdrawal after revoke_until', function () {
    $boundary = now()->addHour()->startOfSecond();
    $user = makeWithdrawDeletionUser();
    $request = makeWithdrawDeletionRequest($user, $boundary);

    $this->travelTo($boundary->copy()->addSecond());

    expect(
        fn () => app(
            WithdrawAccountDeletionAction::class
        )->execute($request->public_id)
    )->toThrow(AccountDeletionCannotWithdraw::class);

    expect($request->refresh()->status)
        ->toBe(AccountDeletionRequestStatus::PendingDeletion)
        ->and($user->refresh()->status)
        ->toBe(UserStatus::PendingDeletion);
});

it('cannot reuse an already withdrawn account deletion request', function () {
    $user = makeWithdrawDeletionUser();
    $request = makeWithdrawDeletionRequest($user);

    app(
        WithdrawAccountDeletionAction::class
    )->execute($request->public_id);

    expect(
        fn () => app(
            WithdrawAccountDeletionAction::class
        )->execute($request->public_id)
    )->toThrow(AccountDeletionCannotWithdraw::class);
});

it('rejects withdrawal when the user is not pending deletion', function () {
    $user = makeWithdrawDeletionUser(UserStatus::Active);
    $request = makeWithdrawDeletionRequest($user);

    expect(
        fn () => app(
            WithdrawAccountDeletionAction::class
        )->execute($request->public_id)
    )->toThrow(AccountDeletionCannotWithdraw::class);

    expect($request->refresh()->status)
        ->toBe(AccountDeletionRequestStatus::PendingDeletion)
        ->and($request->withdrawn_at)
        ->toBeNull()
        ->and($user->refresh()->status)
        ->toBe(UserStatus::Active);
});

it('rolls back withdrawal if audit persistence fails', function () {
    $user = makeWithdrawDeletionUser();
    $request = makeWithdrawDeletionRequest($user);

    AuditEvent::creating(function (): never {
        throw new RuntimeException(
            'Synthetic audit persistence failure.'
        );
    });

    try {
        expect(
            fn () => app(
                WithdrawAccountDeletionAction::class
            )->execute($request->public_id)
        )->toThrow(RuntimeException::class);
    } finally {
        AuditEvent::flushEventListeners();
    }

    expect($request->refresh()->status)
        ->toBe(AccountDeletionRequestStatus::PendingDeletion)
        ->and($request->withdrawn_at)
        ->toBeNull()
        ->and($user->refresh()->status)
        ->toBe(UserStatus::PendingDeletion);
});
