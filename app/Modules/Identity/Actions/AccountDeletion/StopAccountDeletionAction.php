<?php

namespace App\Modules\Identity\Actions\AccountDeletion;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Enums\AccountDeletionRequestStatus;
use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Exceptions\AccountDeletionCannotStop;
use App\Modules\Identity\Models\AccountDeletionRequest;
use App\Modules\Identity\Models\AccountDeletionStopReason;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;

final class StopAccountDeletionAction
{
    public function __construct(
        private readonly AuditWriter $auditWriter,
    ) {}

    public function execute(
        string $publicId,
        int $actorUserId,
        string $reasonKey,
        ?string $comment = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): AccountDeletionRequest {
        return DB::transaction(function () use (
            $publicId,
            $actorUserId,
            $reasonKey,
            $comment,
            $ipAddress,
            $userAgent,
        ): AccountDeletionRequest {
            $stoppedAt = now();

            $deletionRequest = AccountDeletionRequest::query()
                ->where('public_id', $publicId)
                ->lockForUpdate()
                ->first();

            if (
                $deletionRequest === null
                || $deletionRequest->status
                    !== AccountDeletionRequestStatus::PendingDeletion
            ) {
                throw new AccountDeletionCannotStop(
                    'Account deletion cannot be stopped.'
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
                throw new AccountDeletionCannotStop(
                    'Account deletion cannot be stopped.'
                );
            }

            $actor = User::query()
                ->whereKey($actorUserId)
                ->lockForUpdate()
                ->first();

            if (
                $actor === null
                || $actor->status !== UserStatus::Active
            ) {
                throw new AccountDeletionCannotStop(
                    'Account deletion cannot be stopped.'
                );
            }

            $administrationAssignment = $actor
                ->roleAssignments()
                ->whereHas(
                    'role',
                    function ($query): void {
                        $query->where(
                            'key',
                            RoleKey::Administration->value,
                        );
                    },
                )
                ->where(
                    'starts_at',
                    '<=',
                    $stoppedAt,
                )
                ->where(function ($query) use ($stoppedAt): void {
                    $query
                        ->whereNull('ends_at')
                        ->orWhere(
                            'ends_at',
                            '>',
                            $stoppedAt,
                        );
                })
                ->lockForUpdate()
                ->first();

            if ($administrationAssignment === null) {
                throw new AccountDeletionCannotStop(
                    'Account deletion cannot be stopped.'
                );
            }

            $reason = AccountDeletionStopReason::query()
                ->where('key', $reasonKey)
                ->lockForUpdate()
                ->first();

            if (
                $reason === null
                || ! $reason->is_active
            ) {
                throw new AccountDeletionCannotStop(
                    'Account deletion cannot be stopped.'
                );
            }

            $normalizedComment = $comment !== null
                ? trim($comment)
                : null;

            if ($normalizedComment === '') {
                $normalizedComment = null;
            }

            if (
                $reason->requires_comment
                && $normalizedComment === null
            ) {
                throw new AccountDeletionCannotStop(
                    'A stop comment is required.'
                );
            }

            $deletionRequest->status =
                AccountDeletionRequestStatus::Stopped;
            $deletionRequest->stopped_at = $stoppedAt;
            $deletionRequest->stopped_by_user_id = $actor->id;
            $deletionRequest->stop_reason_id = $reason->id;
            $deletionRequest->stop_comment = $normalizedComment;
            $deletionRequest->save();

            /*
             * Die Bestätigung hatte alle Sessions bereits invalidiert.
             * Beim administrativen Stop wird das Konto wieder nutzbar,
             * ohne session_version oder remember_token zurückzusetzen.
             */
            $user->status = UserStatus::Active;
            $user->save();

            $this->auditWriter->write(
                eventKey: AuditEventCatalog::ACCOUNT_DELETION_STOPPED,
                actorType: AuditActorType::User,
                actorUserId: $actor->id,
                subjectType: User::class,
                subjectId: $user->id,
                newValues: [
                    'stopped_at' => $stoppedAt->toIso8601String(),
                    'reason_key' => $reason->key,
                ],
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                occurredAt: $stoppedAt,
            );

            return $deletionRequest;
        });
    }
}
