<?php

namespace App\Modules\Identity\Actions\TwoFactor;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Enums\TwoFactorMethodType;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Exceptions\TwoFactorSetupFailed;
use App\Modules\Identity\Models\TwoFactorMethod;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;

final class EnableEmailTwoFactorAction
{
    public function __construct(
        private readonly AuditWriter $auditWriter,
    ) {
    }

    public function execute(
        User $user,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $deviceInfo = null,
    ): TwoFactorMethod {
        return DB::transaction(function () use (
            $user,
            $ipAddress,
            $userAgent,
            $deviceInfo,
        ): TwoFactorMethod {
            $lockedUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $lockedUser->status
                    !== UserStatus::Active
                || $lockedUser->email_verified_at
                    === null
            ) {
                throw TwoFactorSetupFailed::
                    invalidMethod();
            }

            $existing =
                TwoFactorMethod::query()
                    ->where(
                        'user_id',
                        $lockedUser->id,
                    )
                    ->where(
                        'type',
                        TwoFactorMethodType::Email,
                    )
                    ->whereNotNull('confirmed_at')
                    ->whereNull('disabled_at')
                    ->lockForUpdate()
                    ->first();

            if ($existing !== null) {
                return $existing;
            }

            $method =
                TwoFactorMethod::query()
                    ->create([
                        'user_id' =>
                            $lockedUser->id,
                        'type' =>
                            TwoFactorMethodType::Email,
                        'secret' => null,
                        'confirmed_at' => now(),
                    ]);

            $this->auditWriter->write(
                eventKey:
                    AuditEventCatalog::
                        AUTH_2FA_ENABLED,
                actorType:
                    AuditActorType::User,
                actorUserId:
                    $lockedUser->id,
                subjectType: 'user',
                subjectId: $lockedUser->id,
                newValues: [
                    'method' => 'email',
                ],
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                deviceInfo: $deviceInfo,
            );

            return $method;
        });
    }
}