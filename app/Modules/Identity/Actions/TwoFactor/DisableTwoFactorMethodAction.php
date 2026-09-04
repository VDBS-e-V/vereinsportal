<?php

namespace App\Modules\Identity\Actions\TwoFactor;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Enums\TwoFactorMethodType;
use App\Modules\Identity\Models\TwoFactorMethod;
use App\Modules\Identity\Models\TwoFactorRecoveryCode;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\TwoFactorRequirement;
use Illuminate\Support\Facades\DB;

final class DisableTwoFactorMethodAction
{
    public function __construct(
        private readonly TwoFactorRequirement $requirement,
        private readonly AuditWriter $auditWriter,
    ) {}

    public function execute(
        User $user,
        TwoFactorMethodType $type,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $deviceInfo = null,
    ): void {
        DB::transaction(function () use (
            $user,
            $type,
            $ipAddress,
            $userAgent,
            $deviceInfo,
        ): void {
            $lockedUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $methods =
                TwoFactorMethod::query()
                    ->where(
                        'user_id',
                        $lockedUser->id,
                    )
                    ->where(
                        'type',
                        $type,
                    )
                    ->whereNotNull('confirmed_at')
                    ->whereNull('disabled_at')
                    ->lockForUpdate()
                    ->get();

            if ($methods->isEmpty()) {
                return;
            }

            foreach ($methods as $method) {
                $method->disabled_at = now();
                $method->save();
            }

            $this->auditWriter->write(
                eventKey: AuditEventCatalog::AUTH_2FA_DISABLED,
                actorType: AuditActorType::User,
                actorUserId: $lockedUser->id,
                subjectType: 'user',
                subjectId: $lockedUser->id,
                newValues: [
                    'method' => $type->value,
                ],
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                deviceInfo: $deviceInfo,
            );

            /*
             * Wenn anschließend gar keine 2FA mehr gilt,
             * dürfen alte Recovery Codes nicht als
             * eigenständiger Authentifizierungsweg
             * weiterleben.
             *
             * Pflichtrollen besitzen weiterhin den
             * E-Mail-Faktor.
             */
            if (
                ! $this->requirement
                    ->requiresChallenge(
                        $lockedUser
                    )
            ) {
                TwoFactorRecoveryCode::query()
                    ->where(
                        'user_id',
                        $lockedUser->id,
                    )
                    ->whereNull('used_at')
                    ->whereNull('invalidated_at')
                    ->update([
                        'invalidated_at' => now(),
                    ]);
            }
        });
    }
}
