<?php

namespace App\Modules\Identity\Actions\AccountDeletion;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Enums\AccountDeletionRequestStatus;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Exceptions\AccountDeletionCannotStart;
use App\Modules\Identity\Models\AccountDeletionRequest;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class RequestAccountDeletionAction
{
    public function __construct(
        private readonly AuditWriter $auditWriter,
    ) {}

    public function execute(
        User $user,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): AccountDeletionRequest {
        return DB::transaction(function () use (
            $user,
            $ipAddress,
            $userAgent,
        ): AccountDeletionRequest {
            $lockedUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $lockedUser->status !== UserStatus::Active
                || $lockedUser->email_verified_at === null
            ) {
                throw new AccountDeletionCannotStart(
                    'Account deletion cannot be started for this account.'
                );
            }

            $hasOpenRequest = AccountDeletionRequest::query()
                ->where('user_id', $lockedUser->id)
                ->whereIn('status', [
                    AccountDeletionRequestStatus::PendingConfirmation->value,
                    AccountDeletionRequestStatus::PendingDeletion->value,
                ])
                ->lockForUpdate()
                ->exists();

            if ($hasOpenRequest) {
                throw new AccountDeletionCannotStart(
                    'An open account deletion request already exists.'
                );
            }

            $requestedAt = now();

            $deletionRequest = AccountDeletionRequest::query()->create([
                'public_id' => (string) Str::ulid(),
                'user_id' => $lockedUser->id,
                'status' => AccountDeletionRequestStatus::PendingConfirmation,
                'requested_at' => $requestedAt,
                'confirmation_sent_at' => null,
            ]);

            $this->auditWriter->write(
                eventKey: AuditEventCatalog::ACCOUNT_DELETION_REQUESTED,
                actorType: AuditActorType::User,
                actorUserId: $lockedUser->id,
                subjectType: User::class,
                subjectId: $lockedUser->id,
                newValues: [
                    'requested_at' => $requestedAt->toIso8601String(),
                ],
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                occurredAt: $requestedAt,
            );

            return $deletionRequest;
        });
    }
}
