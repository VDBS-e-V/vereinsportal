<?php

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Actions\AccountDeletion\ConfirmAccountDeletionAction;
use App\Modules\Identity\Enums\AccountDeletionRequestStatus;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Exceptions\AccountDeletionCannotConfirm;
use App\Modules\Identity\Models\AccountDeletionRequest;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function makeConfirmDeletionUser(): User
{
    $user = User::query()->create([
        'email' => 'confirm-delete@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 4,
    ]);

    $user->email_verified_at = now();
    $user->remember_token = 'existing-remember-token';
    $user->save();

    return $user->refresh();
}

function makeConfirmDeletionRequest(
    User $user,
): AccountDeletionRequest {
    return AccountDeletionRequest::query()->create([
        'public_id' => (string) Str::ulid(),
        'user_id' => $user->id,
        'status' => AccountDeletionRequestStatus::PendingConfirmation,
        'requested_at' => now()->subHour(),
        'confirmation_sent_at' => now(),
    ]);
}

it('confirms account deletion and starts the five day revoke period', function () {
    $user = makeConfirmDeletionUser();
    $request = makeConfirmDeletionRequest($user);

    $confirmed = app(
        ConfirmAccountDeletionAction::class
    )->execute(
        publicId: $request->public_id,
        ipAddress: '127.0.0.1',
        userAgent: 'Pest',
    );

    $user->refresh();

    expect($confirmed->status)
        ->toBe(AccountDeletionRequestStatus::PendingDeletion)
        ->and($confirmed->confirmed_at)
        ->not->toBeNull()
        ->and($confirmed->revoke_until)
        ->not->toBeNull()
        ->and(
            $confirmed->revoke_until->timestamp
            - $confirmed->confirmed_at->timestamp
        )
        ->toBe(5 * 24 * 60 * 60)
        ->and($user->status)
        ->toBe(UserStatus::PendingDeletion)
        ->and($user->session_version)
        ->toBe(5)
        ->and($user->remember_token)
        ->toBeNull();

    $audit = AuditEvent::query()
        ->where(
            'event_key',
            AuditEventCatalog::ACCOUNT_DELETION_CONFIRMED,
        )
        ->sole();

    expect($audit->actor_user_id)
        ->toBe($user->id)
        ->and($audit->subject_id)
        ->toBe($user->id)
        ->and($audit->new_values)
        ->toHaveKey('revoke_until');
});

it('invalidates stored sessions and password reset tokens', function () {
    $user = makeConfirmDeletionUser();
    $request = makeConfirmDeletionRequest($user);

    DB::table('sessions')->insert([
        'id' => 'deletion-session',
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
        'payload' => '',
        'last_activity' => now()->timestamp,
    ]);

    DB::table('password_reset_tokens')->insert([
        'email' => $user->email,
        'token' => 'token-hash',
        'created_at' => now(),
    ]);

    app(
        ConfirmAccountDeletionAction::class
    )->execute($request->public_id);

    expect(
        DB::table('sessions')
            ->where('user_id', $user->id)
            ->exists()
    )->toBeFalse()
        ->and(
            DB::table('password_reset_tokens')
                ->where('email', $user->email)
                ->exists()
        )
        ->toBeFalse();
});

it('rejects an expired account deletion confirmation', function () {
    $user = makeConfirmDeletionUser();

    $request = AccountDeletionRequest::query()->create([
        'public_id' => (string) Str::ulid(),
        'user_id' => $user->id,
        'status' => AccountDeletionRequestStatus::PendingConfirmation,
        'requested_at' => now()->subDays(4),
        'confirmation_sent_at' => now()->subDays(3)->subSecond(),
    ]);

    expect(
        fn () => app(
            ConfirmAccountDeletionAction::class
        )->execute($request->public_id)
    )->toThrow(AccountDeletionCannotConfirm::class);

    expect($user->refresh()->status)
        ->toBe(UserStatus::Active);
});

it('cannot reuse an already confirmed account deletion request', function () {
    $user = makeConfirmDeletionUser();
    $request = makeConfirmDeletionRequest($user);

    app(
        ConfirmAccountDeletionAction::class
    )->execute($request->public_id);

    expect(
        fn () => app(
            ConfirmAccountDeletionAction::class
        )->execute($request->public_id)
    )->toThrow(AccountDeletionCannotConfirm::class);
});
