<?php

namespace App\Modules\Identity\Actions\Auth;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Exceptions\LoginFailed;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\LoginRateLimiter;
use App\Modules\Identity\Support\PendingLogin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class FinalizeLoginAction
{
    public function __construct(
        private readonly AuditWriter $auditWriter,
        private readonly LoginRateLimiter $rateLimiter,
        private readonly PendingLogin $pendingLogin,
    ) {}

    public function execute(
        User $user,
        bool $remember,
        string $method,
        ?int $expectedSessionVersion = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $deviceInfo = null,
    ): User {
        $authenticatedUser =
            DB::transaction(function () use (
                $user,
                $remember,
                $method,
                $expectedSessionVersion,
                $ipAddress,
                $userAgent,
                $deviceInfo,
            ): User {
                $lockedUser = User::query()
                    ->whereKey($user->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    $lockedUser->status
                        !== UserStatus::Active
                    || $lockedUser
                        ->email_verified_at === null
                    || (
                        $expectedSessionVersion
                            !== null
                        && $lockedUser
                            ->session_version
                            !== $expectedSessionVersion
                    )
                ) {
                    throw LoginFailed::invalidCredentials();
                }

                $lockedUser->last_login_at =
                    now();

                $lockedUser->save();

                $this->auditWriter->write(
                    eventKey: AuditEventCatalog::AUTH_LOGIN_SUCCEEDED,
                    actorType: AuditActorType::User,
                    actorUserId: $lockedUser->id,
                    subjectType: 'user',
                    subjectId: $lockedUser->id,
                    newValues: [
                        'method' => $method,
                        'remember_me' => $remember,
                    ],
                    ipAddress: $ipAddress,
                    userAgent: $userAgent,
                    deviceInfo: $deviceInfo,
                );

                return $lockedUser;
            });

        /*
         * Erst NACH erfolgreichem Commit entsteht
         * die vollständige Browser-Session.
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
            $authenticatedUser
                ->session_version,
        );

        session()->put(
            'identity.account_validated_at',
            now()->timestamp,
        );

        if ($method === 'password') {
            session()->forget(
                'identity.two_factor_verified_at'
            );
        } else {
            session()->put(
                'identity.two_factor_verified_at',
                now()->timestamp,
            );
        }

        $this->pendingLogin->clear();

        $this->rateLimiter->clearUser(
            $authenticatedUser->email
        );

        return $authenticatedUser;
    }
}
