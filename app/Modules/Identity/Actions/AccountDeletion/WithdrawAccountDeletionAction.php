<?php

namespace App\Modules\Identity\Actions\AccountDeletion;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Enums\AccountDeletionRequestStatus;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Exceptions\AccountDeletionCannotWithdraw;
use App\Modules\Identity\Models\AccountDeletionRequest;
use App\Modules\Identity\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class WithdrawAccountDeletionAction
{
    public function __construct(
        private readonly AuditWriter $auditWriter,
    ) {}

    public function execute(
        string $publicId,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): AccountDeletionRequest {
        return DB::transaction(function () use (
            $publicId,
            $ipAddress,
            $userAgent,
        ): AccountDeletionRequest {
            $deletionRequest = AccountDeletionRequest::query()
                ->where('public_id', $publicId)
                ->lockForUpdate()
                ->first();

            if (
                $deletionRequest === null
                || $deletionRequest->status
                    !== AccountDeletionRequestStatus::PendingDeletion
                || $deletionRequest->revoke_until === null
            ) {
                throw new AccountDeletionCannotWithdraw(
                    'Account deletion cannot be withdrawn.'
                );
            }

            $withdrawnAt = now();
            $revokeUntil = CarbonImmutable::instance(
                $deletionRequest->revoke_until
            );

            if ($withdrawnAt->greaterThan($revokeUntil)) {
                throw new AccountDeletionCannotWithdraw(
                    'Account deletion withdrawal period has expired.'
                );
            }

            $user = User::query()
                ->whereKey($deletionRequest->user_id)
                ->lockForUpdate()
                ->first();

            if (
                $user === null
                || $user->status !== UserStatus::PendingDeletion
            ) {
                throw new AccountDeletionCannotWithdraw(
                    'Account deletion cannot be withdrawn.'
                );
            }

            $deletionRequest->status =
                AccountDeletionRequestStatus::Withdrawn;
            $deletionRequest->withdrawn_at = $withdrawnAt;
            $deletionRequest->save();

            $user->status = UserStatus::Active;
            $user->save();

            $this->auditWriter->write(
                eventKey: AuditEventCatalog::ACCOUNT_DELETION_WITHDRAWN,
                actorType: AuditActorType::User,
                actorUserId: $user->id,
                subjectType: User::class,
                subjectId: $user->id,
                newValues: [
                    'withdrawn_at' => $withdrawnAt->toIso8601String(),
                ],
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                occurredAt: $withdrawnAt,
            );

            return $deletionRequest;
        });
    }
}
