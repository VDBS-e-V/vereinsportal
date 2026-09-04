<?php

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Actions\AccountDeletion\RequestAccountDeletionAction;
use App\Modules\Identity\Enums\AccountDeletionRequestStatus;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Exceptions\AccountDeletionCannotStart;
use App\Modules\Identity\Models\AccountDeletionRequest;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;

function makeDeletionActionUser(
    string $email = 'delete@example.test',
): User {
    $user = User::query()->create([
        'email' => $email,
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 1,
    ]);

    $user->email_verified_at = now();
    $user->save();

    return $user->refresh();
}

it('creates a pending account deletion request atomically with audit', function () {
    $user = makeDeletionActionUser();

    $request = app(
        RequestAccountDeletionAction::class
    )->execute(
        user: $user,
        ipAddress: '127.0.0.1',
        userAgent: 'Pest',
    );

    expect($request->status)
        ->toBe(AccountDeletionRequestStatus::PendingConfirmation)
        ->and($request->user_id)
        ->toBe($user->id)
        ->and($request->requested_at)
        ->not->toBeNull()
        ->and($request->confirmation_sent_at)
        ->toBeNull()
        ->and($user->refresh()->status)
        ->toBe(UserStatus::Active);

    $audit = AuditEvent::query()
        ->where(
            'event_key',
            AuditEventCatalog::ACCOUNT_DELETION_REQUESTED,
        )
        ->sole();

    expect($audit->actor_user_id)
        ->toBe($user->id)
        ->and($audit->subject_id)
        ->toBe($user->id)
        ->and($audit->new_values)
        ->toHaveKey('requested_at');
});

it('rejects another open account deletion request', function () {
    $user = makeDeletionActionUser();

    app(
        RequestAccountDeletionAction::class
    )->execute($user);

    expect(
        fn () => app(
            RequestAccountDeletionAction::class
        )->execute($user)
    )->toThrow(AccountDeletionCannotStart::class);

    expect(
        AccountDeletionRequest::query()
            ->where('user_id', $user->id)
            ->count()
    )->toBe(1);
});

it('rejects deletion requests for an unusable account', function () {
    $user = makeDeletionActionUser();

    $user->status = UserStatus::Disabled;
    $user->save();

    expect(
        fn () => app(
            RequestAccountDeletionAction::class
        )->execute($user)
    )->toThrow(AccountDeletionCannotStart::class);

    expect(AccountDeletionRequest::query()->count())
        ->toBe(0);
});

it('rolls the request and audit back with an outer transaction', function () {
    $user = makeDeletionActionUser();

    expect(
        fn () => DB::transaction(function () use ($user): void {
            app(
                RequestAccountDeletionAction::class
            )->execute($user);

            throw new RuntimeException(
                'Force outer transaction rollback.'
            );
        })
    )->toThrow(
        RuntimeException::class,
        'Force outer transaction rollback.',
    );

    expect(AccountDeletionRequest::query()->count())
        ->toBe(0)
        ->and(
            AuditEvent::query()
                ->where(
                    'event_key',
                    AuditEventCatalog::ACCOUNT_DELETION_REQUESTED,
                )
                ->count()
        )
        ->toBe(0);
});
