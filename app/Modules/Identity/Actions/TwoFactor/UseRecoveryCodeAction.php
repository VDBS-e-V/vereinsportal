<?php

namespace App\Modules\Identity\Actions\TwoFactor;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Exceptions\TwoFactorChallengeFailed;
use App\Modules\Identity\Models\TwoFactorRecoveryCode;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\RecoveryCodeService;
use App\Modules\Identity\Support\TwoFactorRateLimiter;
use Illuminate\Support\Facades\DB;

final class UseRecoveryCodeAction
{
    public function __construct(
        private readonly RecoveryCodeService $recoveryCodes,
        private readonly TwoFactorRateLimiter $rateLimiter,
        private readonly AuditWriter $auditWriter,
    ) {}

    public function execute(
        User $user,
        string $code,
        string $ipAddress,
        ?string $userAgent = null,
        ?array $deviceInfo = null,
    ): void {
        if (
            $this->rateLimiter
                ->tooManyIpAttempts($ipAddress)
            || $this->rateLimiter
                ->tooManyUserAttempts($user->id)
        ) {
            throw TwoFactorChallengeFailed::locked();
        }

        /*
         * Erfolgreicher Verbrauch + Erfolgs-Audit
         * bleiben atomar.
         *
         * Ein ungültiger Code führt dagegen zu
         * keiner DB-Mutation und wird erst nach
         * sauber beendetem Transaction-Block
         * als Security-Fehler auditiert.
         */
        $used = DB::transaction(
            function () use (
                $user,
                $code,
                $ipAddress,
                $userAgent,
                $deviceInfo,
            ): bool {
                $candidates =
                    TwoFactorRecoveryCode::query()
                        ->where(
                            'user_id',
                            $user->id,
                        )
                        ->whereNull('used_at')
                        ->whereNull(
                            'invalidated_at'
                        )
                        ->lockForUpdate()
                        ->get();

                $matching = null;

                foreach (
                    $candidates as $candidate
                ) {
                    if (
                        $this->recoveryCodes
                            ->matches(
                                $code,
                                $candidate
                                    ->code_hash,
                            )
                    ) {
                        $matching = $candidate;

                        break;
                    }
                }

                if ($matching === null) {
                    return false;
                }

                $matching->used_at = now();
                $matching->save();

                $this->auditWriter->write(
                    eventKey: AuditEventCatalog::AUTH_2FA_RECOVERY_CODE_USED,
                    actorType: AuditActorType::User,
                    actorUserId: $user->id,
                    subjectType: 'user',
                    subjectId: $user->id,
                    ipAddress: $ipAddress,
                    userAgent: $userAgent,
                    deviceInfo: $deviceInfo,
                );

                return true;
            },
        );

        if (! $used) {
            $this->rateLimiter->hitFailure(
                userId: $user->id,
                ipAddress: $ipAddress,
            );

            $this->auditWriter->write(
                eventKey: AuditEventCatalog::AUTH_2FA_CHALLENGE_FAILED,
                actorType: AuditActorType::User,
                actorUserId: $user->id,
                subjectType: 'user',
                subjectId: $user->id,
                newValues: [
                    'method' => 'recovery_code',
                ],
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                deviceInfo: $deviceInfo,
            );

            throw TwoFactorChallengeFailed::invalidCode();
        }

        $this->rateLimiter
            ->clearUser($user->id);
    }
}
