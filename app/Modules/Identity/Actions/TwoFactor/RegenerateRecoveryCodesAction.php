<?php

namespace App\Modules\Identity\Actions\TwoFactor;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Exceptions\TwoFactorSetupFailed;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\RecoveryCodeService;
use App\Modules\Identity\Support\TwoFactorRequirement;
use Illuminate\Support\Facades\DB;

final class RegenerateRecoveryCodesAction
{
    public function __construct(
        private readonly TwoFactorRequirement $requirement,
        private readonly RecoveryCodeService $recoveryCodes,
        private readonly AuditWriter $auditWriter,
    ) {}

    /**
     * @return list<string>
     */
    public function execute(
        User $user,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $deviceInfo = null,
    ): array {
        return DB::transaction(function () use (
            $user,
            $ipAddress,
            $userAgent,
            $deviceInfo,
        ): array {
            $lockedUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                ! $this->requirement
                    ->requiresChallenge(
                        $lockedUser
                    )
            ) {
                throw TwoFactorSetupFailed::invalidMethod();
            }

            $codes =
                $this->recoveryCodes
                    ->replaceFor($lockedUser);

            $this->auditWriter->write(
                eventKey: AuditEventCatalog::AUTH_2FA_RECOVERY_CODES_REGENERATED,
                actorType: AuditActorType::User,
                actorUserId: $lockedUser->id,
                subjectType: 'user',
                subjectId: $lockedUser->id,
                newValues: [
                    'count' => 4,
                ],
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                deviceInfo: $deviceInfo,
            );

            return $codes;
        });
    }
}
