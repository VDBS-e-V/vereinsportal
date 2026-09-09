<?php

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Actions\AccountDeletion\StopAccountDeletionAction;
use App\Modules\Identity\Enums\AccountDeletionRequestStatus;
use App\Modules\Identity\Enums\RoleAssignmentSource;
use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Exceptions\AccountDeletionCannotStop;
use App\Modules\Identity\Models\AccountDeletionRequest;
use App\Modules\Identity\Models\AccountDeletionStopReason;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\RoleAssignment;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Str;

function makeStopDeletionUser(
    UserStatus $status = UserStatus::PendingDeletion,
): User {
    $user = User::query()->create([
        'email' => 'stop-delete-'.Str::lower((string) Str::ulid()).'@example.test',
        'password' => 'Sicher123!',
        'status' => $status,
        'session_version' => 11,
    ]);

    $user->email_verified_at = now();
    $user->remember_token = null;
    $user->save();

    return $user->refresh();
}

function makeStopDeletionRequest(
    User $user,
    AccountDeletionRequestStatus $status = AccountDeletionRequestStatus::PendingDeletion,
    mixed $revokeUntil = null,
): AccountDeletionRequest {
    return AccountDeletionRequest::query()->create([
        'public_id' => (string) Str::ulid(),
        'user_id' => $user->id,
        'status' => $status,
        'requested_at' => now()->subDay(),
        'confirmation_sent_at' => now()->subDay(),
        'confirmed_at' => now()->subHours(2),
        'revoke_until' => $revokeUntil ?? now()->addDays(5),
    ]);
}

function makeStopAdministrator(
    bool $endedRole = false,
): User {
    $administrator = User::query()->create([
        'email' => 'stop-admin-'.Str::lower((string) Str::ulid()).'@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 3,
    ]);

    $administrator->email_verified_at = now();
    $administrator->save();

    $role = Role::query()->create([
        'key' => RoleKey::Administration,
        'name' => 'Administration',
        'is_system' => true,
    ]);

    RoleAssignment::query()->create([
        'user_id' => $administrator->id,
        'role_id' => $role->id,
        'source' => RoleAssignmentSource::Console,
        'starts_at' => now()->subDays(2),
        'ends_at' => $endedRole
            ? now()->subDay()
            : null,
    ]);

    return $administrator->refresh();
}

function makeStopReason(
    bool $requiresComment = false,
    bool $active = true,
): AccountDeletionStopReason {
    return AccountDeletionStopReason::query()->create([
        'key' => 'test_stop_reason',
        'label' => 'Testgrund',
        'requires_comment' => $requiresComment,
        'is_active' => $active,
    ]);
}

it('stops a pending account deletion atomically', function () {
    $user = makeStopDeletionUser();
    $request = makeStopDeletionRequest($user);
    $administrator = makeStopAdministrator();
    $reason = makeStopReason();

    $stopped = app(
        StopAccountDeletionAction::class
    )->execute(
        publicId: $request->public_id,
        actorUserId: $administrator->id,
        reasonKey: $reason->key,
        comment: '  Administrativ geprüft.  ',
        ipAddress: '127.0.0.1',
        userAgent: 'Pest',
    );

    $user->refresh();

    expect($stopped->status)
        ->toBe(AccountDeletionRequestStatus::Stopped)
        ->and($stopped->stopped_at)
        ->not->toBeNull()
        ->and($stopped->stopped_by_user_id)
        ->toBe($administrator->id)
        ->and($stopped->stop_reason_id)
        ->toBe($reason->id)
        ->and($stopped->stop_comment)
        ->toBe('Administrativ geprüft.')
        ->and($user->status)
        ->toBe(UserStatus::Active)
        ->and($user->session_version)
        ->toBe(11)
        ->and($user->remember_token)
        ->toBeNull();

    $audit = AuditEvent::query()
        ->where(
            'event_key',
            AuditEventCatalog::ACCOUNT_DELETION_STOPPED,
        )
        ->sole();

    expect($audit->actor_user_id)
        ->toBe($administrator->id)
        ->and($audit->subject_id)
        ->toBe($user->id)
        ->and($audit->new_values)
        ->toHaveKeys([
            'stopped_at',
            'reason_key',
        ])
        ->and($audit->new_values['reason_key'])
        ->toBe($reason->key);
});

it('requires a comment when the stop reason requires one', function () {
    $user = makeStopDeletionUser();
    $request = makeStopDeletionRequest($user);
    $administrator = makeStopAdministrator();
    $reason = makeStopReason(
        requiresComment: true,
    );

    expect(
        fn () => app(
            StopAccountDeletionAction::class
        )->execute(
            publicId: $request->public_id,
            actorUserId: $administrator->id,
            reasonKey: $reason->key,
            comment: '   ',
        )
    )->toThrow(AccountDeletionCannotStop::class);

    expect($request->refresh()->status)
        ->toBe(AccountDeletionRequestStatus::PendingDeletion)
        ->and($request->stopped_at)
        ->toBeNull()
        ->and($user->refresh()->status)
        ->toBe(UserStatus::PendingDeletion);
});

it('rejects an inactive stop reason', function () {
    $user = makeStopDeletionUser();
    $request = makeStopDeletionRequest($user);
    $administrator = makeStopAdministrator();
    $reason = makeStopReason(
        active: false,
    );

    expect(
        fn () => app(
            StopAccountDeletionAction::class
        )->execute(
            publicId: $request->public_id,
            actorUserId: $administrator->id,
            reasonKey: $reason->key,
        )
    )->toThrow(AccountDeletionCannotStop::class);

    expect($request->refresh()->status)
        ->toBe(AccountDeletionRequestStatus::PendingDeletion)
        ->and($user->refresh()->status)
        ->toBe(UserStatus::PendingDeletion);
});

it('rejects an unknown stop reason', function () {
    $user = makeStopDeletionUser();
    $request = makeStopDeletionRequest($user);
    $administrator = makeStopAdministrator();

    expect(
        fn () => app(
            StopAccountDeletionAction::class
        )->execute(
            publicId: $request->public_id,
            actorUserId: $administrator->id,
            reasonKey: 'unknown_stop_reason',
        )
    )->toThrow(AccountDeletionCannotStop::class);

    expect($request->refresh()->status)
        ->toBe(AccountDeletionRequestStatus::PendingDeletion)
        ->and($user->refresh()->status)
        ->toBe(UserStatus::PendingDeletion);
});

it('rejects an actor without an active administration role', function () {
    $user = makeStopDeletionUser();
    $request = makeStopDeletionRequest($user);
    $reason = makeStopReason();

    $actor = User::query()->create([
        'email' => 'unauthorized-stop@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    expect(
        fn () => app(
            StopAccountDeletionAction::class
        )->execute(
            publicId: $request->public_id,
            actorUserId: $actor->id,
            reasonKey: $reason->key,
        )
    )->toThrow(AccountDeletionCannotStop::class);

    expect($request->refresh()->status)
        ->toBe(AccountDeletionRequestStatus::PendingDeletion);
});

it('rejects an actor whose administration role has ended', function () {
    $user = makeStopDeletionUser();
    $request = makeStopDeletionRequest($user);
    $administrator = makeStopAdministrator(
        endedRole: true,
    );
    $reason = makeStopReason();

    expect(
        fn () => app(
            StopAccountDeletionAction::class
        )->execute(
            publicId: $request->public_id,
            actorUserId: $administrator->id,
            reasonKey: $reason->key,
        )
    )->toThrow(AccountDeletionCannotStop::class);

    expect($request->refresh()->status)
        ->toBe(AccountDeletionRequestStatus::PendingDeletion);
});

it('rejects stopping when the affected user is not pending deletion', function () {
    $user = makeStopDeletionUser(
        UserStatus::Active,
    );
    $request = makeStopDeletionRequest($user);
    $administrator = makeStopAdministrator();
    $reason = makeStopReason();

    expect(
        fn () => app(
            StopAccountDeletionAction::class
        )->execute(
            publicId: $request->public_id,
            actorUserId: $administrator->id,
            reasonKey: $reason->key,
        )
    )->toThrow(AccountDeletionCannotStop::class);

    expect($request->refresh()->status)
        ->toBe(AccountDeletionRequestStatus::PendingDeletion)
        ->and($request->stopped_at)
        ->toBeNull()
        ->and($user->refresh()->status)
        ->toBe(UserStatus::Active);
});

it('rejects terminal deletion request states', function (
    AccountDeletionRequestStatus $status,
) {
    $user = makeStopDeletionUser();
    $request = makeStopDeletionRequest(
        user: $user,
        status: $status,
    );
    $administrator = makeStopAdministrator();
    $reason = makeStopReason();

    expect(
        fn () => app(
            StopAccountDeletionAction::class
        )->execute(
            publicId: $request->public_id,
            actorUserId: $administrator->id,
            reasonKey: $reason->key,
        )
    )->toThrow(AccountDeletionCannotStop::class);
})->with([
    AccountDeletionRequestStatus::Withdrawn,
    AccountDeletionRequestStatus::Stopped,
    AccountDeletionRequestStatus::Completed,
]);

it('allows an administrative stop after the revoke window expired', function () {
    $user = makeStopDeletionUser();
    $request = makeStopDeletionRequest(
        user: $user,
        revokeUntil: now()->subMinute(),
    );
    $administrator = makeStopAdministrator();
    $reason = makeStopReason();

    $stopped = app(
        StopAccountDeletionAction::class
    )->execute(
        publicId: $request->public_id,
        actorUserId: $administrator->id,
        reasonKey: $reason->key,
    );

    expect($stopped->status)
        ->toBe(AccountDeletionRequestStatus::Stopped)
        ->and($user->refresh()->status)
        ->toBe(UserStatus::Active);
});

it('rolls back the stop when audit persistence fails', function () {
    $user = makeStopDeletionUser();
    $request = makeStopDeletionRequest($user);
    $administrator = makeStopAdministrator();
    $reason = makeStopReason();

    AuditEvent::creating(function (): never {
        throw new RuntimeException(
            'Synthetic audit persistence failure.'
        );
    });

    try {
        expect(
            fn () => app(
                StopAccountDeletionAction::class
            )->execute(
                publicId: $request->public_id,
                actorUserId: $administrator->id,
                reasonKey: $reason->key,
            )
        )->toThrow(RuntimeException::class);
    } finally {
        AuditEvent::flushEventListeners();
    }

    expect($request->refresh()->status)
        ->toBe(AccountDeletionRequestStatus::PendingDeletion)
        ->and($request->stopped_at)
        ->toBeNull()
        ->and($request->stopped_by_user_id)
        ->toBeNull()
        ->and($request->stop_reason_id)
        ->toBeNull()
        ->and($user->refresh()->status)
        ->toBe(UserStatus::PendingDeletion);
});
