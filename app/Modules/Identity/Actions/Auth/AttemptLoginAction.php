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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class AttemptLoginAction
{
    public function __construct(
        private readonly LoginRateLimiter $rateLimiter,
        private readonly AuditWriter $auditWriter,
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
        $email = EmailNormalizer::normalize($email);

        if ($this->rateLimiter->tooManyIpAttempts($ipAddress)) {
            $this->auditLocked(
                email: $email,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                deviceInfo: $deviceInfo,
                scope: 'ip',
                lockedForSeconds:
                    $this->rateLimiter->ipAvailableIn($ipAddress),
            );

            throw LoginFailed::temporarilyLocked();
        }

        if ($this->rateLimiter->tooManyUserAttempts($email)) {
            $this->auditLocked(
                email: $email,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                deviceInfo: $deviceInfo,
                scope: 'user',
                lockedForSeconds:
                    $this->rateLimiter->userAvailableIn($email),
            );

            throw LoginFailed::temporarilyLocked();
        }

        $user = User::query()
            ->where('email', $email)
            ->first();

        $valid = $user !== null
            && Hash::check($password, $user->password)
            && $user->status === UserStatus::Active
            && $user->email_verified_at !== null;

        if (! $valid) {
            $this->rateLimiter->hitFailure(
                email: $email,
                ipAddress: $ipAddress,
            );

            $this->auditWriter->write(
                eventKey: AuditEventCatalog::AUTH_LOGIN_FAILED,
                actorType: AuditActorType::User,
                actorUserId: $user?->id,
                actorContext: 'login',
                subjectType: $user !== null ? 'user' : null,
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

            throw LoginFailed::invalidCredentials();
        }

        $authenticatedUser = DB::transaction(
            function () use (
                $user,
                $email,
                $ipAddress,
                $userAgent,
                $deviceInfo,
                $remember,
            ): User {
                $lockedUser = User::query()
                    ->whereKey($user->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    $lockedUser->status !== UserStatus::Active
                    || $lockedUser->email_verified_at === null
                ) {
                    $this->rateLimiter->hitFailure(
                        email: $email,
                        ipAddress: $ipAddress,
                    );

                    throw LoginFailed::invalidCredentials();
                }

                $lockedUser->last_login_at = now();
                $lockedUser->save();

                $this->auditWriter->write(
                    eventKey:
                        AuditEventCatalog::AUTH_LOGIN_SUCCEEDED,
                    actorType: AuditActorType::User,
                    actorUserId: $lockedUser->id,
                    subjectType: 'user',
                    subjectId: $lockedUser->id,
                    newValues: [
                        'method' => 'password',
                        'remember_me' => $remember,
                    ],
                    ipAddress: $ipAddress,
                    userAgent: $userAgent,
                    deviceInfo: $deviceInfo,
                );

                return $lockedUser;
            }
        );

        /*
         * Session-/Cookie-Seiteneffekt erst nach erfolgreichem
         * Commit von last_login_at + Audit.
         */
        $guard = Auth::guard();

        $guard->setRememberDuration(
            (int) config(
                'auth.remember_duration',
                60 * 24 * 30,
            )
        );

        $guard->login(
            $authenticatedUser,
            $remember,
        );

        session()->regenerate();

        session()->put(
            'identity.session_version',
            $authenticatedUser->session_version,
        );

        session()->put(
            'identity.account_validated_at',
            now()->timestamp,
        );

        /*
         * Bewusst nur User-Limiter leeren.
         * Der IP-Limiter bleibt erhalten.
         */
        $this->rateLimiter->clearUser($email);

        return $authenticatedUser;
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
            eventKey: AuditEventCatalog::AUTH_LOGIN_LOCKED,
            actorType: AuditActorType::System,
            actorUserId: null,
            actorContext: 'login',
            subjectType: $user !== null ? 'user' : null,
            subjectId: $user?->id,
            newValues: [
                'scope' => $scope,
                'locked_until' => now()
                    ->addSeconds($lockedForSeconds)
                    ->toISOString(),
            ],
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            deviceInfo: $deviceInfo,
        );
    }
}