<?php

namespace App\Modules\Identity\Actions\AccountDeletion;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Enums\AccountDeletionRequestStatus;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Exceptions\AccountDeletionCannotConfirm;
use App\Modules\Identity\Models\AccountDeletionRequest;
use App\Modules\Identity\Models\TwoFactorEmailChallenge;
use App\Modules\Identity\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class ConfirmAccountDeletionAction
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
                    !== AccountDeletionRequestStatus::PendingConfirmation
                || $deletionRequest->confirmation_sent_at === null
            ) {
                throw new AccountDeletionCannotConfirm(
                    'Account deletion cannot be confirmed.'
                );
            }

            $confirmationExpiresAt = CarbonImmutable::instance(
                $deletionRequest->confirmation_sent_at
            )->addDays(3);

            if ($confirmationExpiresAt->isPast()) {
                throw new AccountDeletionCannotConfirm(
                    'Account deletion confirmation has expired.'
                );
            }

            $user = User::query()
                ->whereKey($deletionRequest->user_id)
                ->lockForUpdate()
                ->first();

            if (
                $user === null
                || $user->status !== UserStatus::Active
            ) {
                throw new AccountDeletionCannotConfirm(
                    'Account deletion cannot be confirmed.'
                );
            }

            $confirmedAt = now();
            $revokeUntil = CarbonImmutable::instance(
                $confirmedAt
            )->addDays(5);

            $deletionRequest->status =
                AccountDeletionRequestStatus::PendingDeletion;

            $deletionRequest->confirmed_at = $confirmedAt;
            $deletionRequest->revoke_until = $revokeUntil;
            $deletionRequest->save();

            $user->status = UserStatus::PendingDeletion;
            $user->session_version++;
            $user->remember_token = null;
            $user->save();

            DB::table('sessions')
                ->where('user_id', $user->id)
                ->delete();

            DB::table('password_reset_tokens')
                ->where('email', $user->email)
                ->delete();

            TwoFactorEmailChallenge::query()
                ->where('user_id', $user->id)
                ->delete();

            $this->auditWriter->write(
                eventKey: AuditEventCatalog::ACCOUNT_DELETION_CONFIRMED,
                actorType: AuditActorType::User,
                actorUserId: $user->id,
                subjectType: User::class,
                subjectId: $user->id,
                newValues: [
                    'revoke_until' => $revokeUntil->toIso8601String(),
                ],
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                occurredAt: $confirmedAt,
            );

            return $deletionRequest;
        });
    }
}
