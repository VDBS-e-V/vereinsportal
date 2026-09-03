<?php

namespace App\Modules\Identity\Actions\TwoFactor;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Enums\TwoFactorMethodType;
use App\Modules\Identity\Exceptions\TwoFactorChallengeFailed;
use App\Modules\Identity\Models\TwoFactorMethod;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\TotpService;
use App\Modules\Identity\Support\TwoFactorRateLimiter;

final class VerifyTotpChallengeAction
{
    public function __construct(
        private readonly TotpService $totp,
        private readonly TwoFactorRateLimiter $rateLimiter,
        private readonly AuditWriter $auditWriter,
    ) {
    }

    public function execute(
        User $user,
        string $code,
        string $ipAddress,
        ?string $userAgent = null,
        ?array $deviceInfo = null,
    ): void {
        if (
            $this->rateLimiter
                ->tooManyIpAttempts(
                    $ipAddress
                )
            || $this->rateLimiter
                ->tooManyUserAttempts(
                    $user->id
                )
        ) {
            throw TwoFactorChallengeFailed::
                locked();
        }

        $methods =
            TwoFactorMethod::query()
                ->where(
                    'user_id',
                    $user->id,
                )
                ->where(
                    'type',
                    TwoFactorMethodType::Totp,
                )
                ->whereNotNull('confirmed_at')
                ->whereNull('disabled_at')
                ->get();

        foreach ($methods as $method) {
            if (
                $method->secret !== null
                && $this->totp->verify(
                    $method->secret,
                    $code,
                )
            ) {
                $this->rateLimiter
                    ->clearUser($user->id);

                return;
            }
        }

        $this->rateLimiter->hitFailure(
            userId: $user->id,
            ipAddress: $ipAddress,
        );

        $this->auditWriter->write(
            eventKey:
                AuditEventCatalog::
                    AUTH_2FA_CHALLENGE_FAILED,
            actorType:
                AuditActorType::User,
            actorUserId: $user->id,
            subjectType: 'user',
            subjectId: $user->id,
            newValues: [
                'method' => 'totp',
            ],
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            deviceInfo: $deviceInfo,
        );

        throw TwoFactorChallengeFailed::
            invalidCode();
    }
}