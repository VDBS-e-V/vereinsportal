<?php

namespace App\Modules\Identity\Actions\TwoFactor;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Enums\TwoFactorMethodType;
use App\Modules\Identity\Exceptions\TwoFactorSetupFailed;
use App\Modules\Identity\Models\TwoFactorMethod;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\RecoveryCodeService;
use App\Modules\Identity\Services\TotpService;
use Illuminate\Support\Facades\DB;

final class ConfirmTotpSetupAction
{
    public function __construct(
        private readonly TotpService $totp,
        private readonly RecoveryCodeService $recoveryCodes,
        private readonly AuditWriter $auditWriter,
    ) {}

    /**
     * @return list<string>
     */
    public function execute(
        User $user,
        int $methodId,
        string $code,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $deviceInfo = null,
    ): array {
        return DB::transaction(function () use (
            $user,
            $methodId,
            $code,
            $ipAddress,
            $userAgent,
            $deviceInfo,
        ): array {
            $lockedUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $method =
                TwoFactorMethod::query()
                    ->whereKey($methodId)
                    ->where(
                        'user_id',
                        $lockedUser->id,
                    )
                    ->where(
                        'type',
                        TwoFactorMethodType::Totp,
                    )
                    ->lockForUpdate()
                    ->first();

            if (
                $method === null
                || $method->confirmed_at !== null
                || $method->disabled_at !== null
                || $method->secret === null
            ) {
                throw TwoFactorSetupFailed::invalidMethod();
            }

            if (
                ! $this->totp->verify(
                    $method->secret,
                    $code,
                )
            ) {
                throw TwoFactorSetupFailed::invalidCode();
            }

            $disabledOldMethod =
                TwoFactorMethod::query()
                    ->where(
                        'user_id',
                        $lockedUser->id,
                    )
                    ->where(
                        'type',
                        TwoFactorMethodType::Totp,
                    )
                    ->where(
                        'id',
                        '<>',
                        $method->id,
                    )
                    ->whereNotNull('confirmed_at')
                    ->whereNull('disabled_at')
                    ->update([
                        'disabled_at' => now(),
                    ]);

            if ($disabledOldMethod > 0) {
                $this->auditWriter->write(
                    eventKey: AuditEventCatalog::AUTH_2FA_DISABLED,
                    actorType: AuditActorType::User,
                    actorUserId: $lockedUser->id,
                    subjectType: 'user',
                    subjectId: $lockedUser->id,
                    newValues: [
                        'method' => 'totp',
                    ],
                    ipAddress: $ipAddress,
                    userAgent: $userAgent,
                    deviceInfo: $deviceInfo,
                );
            }

            $method->confirmed_at = now();
            $method->save();

            $plainCodes =
                $this->recoveryCodes
                    ->replaceFor($lockedUser);

            $this->auditWriter->write(
                eventKey: AuditEventCatalog::AUTH_2FA_ENABLED,
                actorType: AuditActorType::User,
                actorUserId: $lockedUser->id,
                subjectType: 'user',
                subjectId: $lockedUser->id,
                newValues: [
                    'method' => 'totp',
                ],
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                deviceInfo: $deviceInfo,
            );

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

            return $plainCodes;
        });
    }
}
