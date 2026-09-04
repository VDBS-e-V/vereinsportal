<?php

namespace App\Modules\Identity\Actions\TwoFactor;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Exceptions\TwoFactorChallengeFailed;
use App\Modules\Identity\Models\TwoFactorEmailChallenge;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\TwoFactorRateLimiter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class VerifyEmailTwoFactorChallengeAction
{
    public function __construct(
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
         * Nur der erfolgreiche Verbrauch des Challenges
         * ist eine fachliche Mutation.
         *
         * Bei einem falschen Code darf die Transaktion
         * ohne Exception sauber enden, damit das danach
         * geschriebene Security-Audit nicht durch einen
         * Rollback verloren geht.
         */
        $valid = DB::transaction(
            function () use (
                $user,
                $code,
            ): bool {
                $challenge =
                    TwoFactorEmailChallenge::query()
                        ->where(
                            'user_id',
                            $user->id,
                        )
                        ->whereNotNull('sent_at')
                        ->whereNull('used_at')
                        ->whereNull('invalidated_at')
                        ->latest('id')
                        ->lockForUpdate()
                        ->first();

                if (
                    $challenge === null
                    || $challenge
                        ->expires_at
                        ->isPast()
                    || ! Hash::check(
                        trim($code),
                        $challenge->code_hash,
                    )
                ) {
                    return false;
                }

                $challenge->used_at = now();
                $challenge->save();

                return true;
            },
        );

        if (! $valid) {
            $this->recordFailure(
                user: $user,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                deviceInfo: $deviceInfo,
            );

            throw TwoFactorChallengeFailed::invalidCode();
        }

        /*
         * Analog zum Passwort-Login:
         * User-Limit leeren, nicht den globalen
         * IP-Limiter.
         */
        $this->rateLimiter
            ->clearUser($user->id);
    }

    private function recordFailure(
        User $user,
        string $ipAddress,
        ?string $userAgent,
        ?array $deviceInfo,
    ): void {
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
                'method' => 'email',
            ],
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            deviceInfo: $deviceInfo,
        );
    }
}
