<?php

namespace App\Modules\Identity\Actions\Auth;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Exceptions\LoginFailed;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\EmailNormalizer;
use App\Modules\Identity\Support\LoginRateLimiter;
use App\Modules\Identity\Support\PendingLogin;
use App\Modules\Identity\Support\TwoFactorRequirement;
use Illuminate\Support\Facades\Hash;

final class AttemptLoginAction
{
    public function __construct(
        private readonly LoginRateLimiter $rateLimiter,
        private readonly AuditWriter $auditWriter,
        private readonly TwoFactorRequirement $twoFactor,
        private readonly PendingLogin $pendingLogin,
        private readonly FinalizeLoginAction $finalizeLogin,
    ) {
    }

    public function execute(
        string $email,
        string $password,
        bool $remember,
        string $ipAddress,
        ?string $userAgent = null,
        ?array $deviceInfo = null,
    ): User {
        $email =
            EmailNormalizer::normalize($email);

        if (
            $this->rateLimiter
                ->tooManyIpAttempts(
                    $ipAddress
                )
        ) {
            $this->auditLocked(
                email: $email,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                deviceInfo: $deviceInfo,
                scope: 'ip',
                lockedForSeconds:
                    $this->rateLimiter
                        ->ipAvailableIn(
                            $ipAddress
                        ),
            );

            throw LoginFailed::
                temporarilyLocked();
        }

        if (
            $this->rateLimiter
                ->tooManyUserAttempts(
                    $email
                )
        ) {
            $this->auditLocked(
                email: $email,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                deviceInfo: $deviceInfo,
                scope: 'user',
                lockedForSeconds:
                    $this->rateLimiter
                        ->userAvailableIn(
                            $email
                        ),
            );

            throw LoginFailed::
                temporarilyLocked();
        }

        $user = User::query()
            ->where('email', $email)
            ->first();

        $valid =
            $user !== null
            && Hash::check(
                $password,
                $user->password,
            )
            && $user->status
                === UserStatus::Active
            && $user->email_verified_at
                !== null;

        if (! $valid) {
            $this->rateLimiter->hitFailure(
                email: $email,
                ipAddress: $ipAddress,
            );

            $this->auditWriter->write(
                eventKey:
                    AuditEventCatalog::
                        AUTH_LOGIN_FAILED,
                actorType:
                    AuditActorType::User,
                actorUserId: $user?->id,
                actorContext: 'login',
                subjectType:
                    $user !== null
                        ? 'user'
                        : null,
                subjectId: $user?->id,
                newValues: [
                    'reason' =>
                        'invalid_credentials_or_account_state',
                    'login_id' => $email,
                ],
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                deviceInfo: $deviceInfo,
            );

            throw LoginFailed::
                invalidCredentials();
        }

        if (
            $this->twoFactor
                ->requiresChallenge($user)
        ) {
            /*
             * Session-ID bereits nach erfolgreicher
             * Passwortprüfung erneuern, obwohl der User
             * noch NICHT authentifiziert wird.
             */
            session()->regenerate();

            $this->pendingLogin->start(
                user: $user,
                remember: $remember,
            );

            /*
             * Passwort war korrekt:
             * User-Limiter darf zurückgesetzt werden.
             * IP-Limiter bleibt bestehen.
             */
            $this->rateLimiter
                ->clearUser($email);

            return $user;
        }

        return $this->finalizeLogin
            ->execute(
                user: $user,
                remember: $remember,
                method: 'password',
                expectedSessionVersion:
                    $user->session_version,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                deviceInfo: $deviceInfo,
            );
    }

    private function auditLocked(
        string $email,
        string $ipAddress,
        ?string $userAgent,
        ?array $deviceInfo,
        string $scope,
        int $lockedForSeconds,
    ): void {
        $user = User::query()
            ->where('email', $email)
            ->first();

        $this->auditWriter->write(
            eventKey:
                AuditEventCatalog::
                    AUTH_LOGIN_LOCKED,
            actorType:
                AuditActorType::System,
            actorUserId: null,
            actorContext: 'login',
            subjectType:
                $user !== null
                    ? 'user'
                    : null,
            subjectId: $user?->id,
            newValues: [
                'scope' => $scope,
                'locked_until' => now()
                    ->addSeconds(
                        $lockedForSeconds
                    )
                    ->toISOString(),
            ],
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            deviceInfo: $deviceInfo,
        );
    }
}