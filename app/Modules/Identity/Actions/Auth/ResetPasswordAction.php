<?php

namespace App\Modules\Identity\Actions\Auth;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Exceptions\PasswordResetFailed;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\EmailNormalizer;
use App\Modules\Identity\Support\PasswordRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

final class ResetPasswordAction
{
    public function __construct(
        private readonly AuditWriter $auditWriter,
    ) {}

    public function execute(
        string $email,
        string $token,
        string $password,
        string $passwordConfirmation,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $deviceInfo = null,
    ): User {
        $email = EmailNormalizer::normalize($email);

        Validator::make(
            [
                'password' => $password,
                'password_confirmation' => $passwordConfirmation,
            ],
            [
                'password' => [
                    'required',
                    'string',
                    'confirmed',
                    PasswordRules::default(),
                ],
            ],
        )->validate();

        $resetUser = null;

        $status = DB::transaction(function () use (
            $email,
            $token,
            $password,
            $passwordConfirmation,
            $ipAddress,
            $userAgent,
            $deviceInfo,
            &$resetUser,
        ): string {
            return Password::broker()->reset(
                [
                    'email' => $email,
                    'token' => $token,
                    'password' => $password,
                    'password_confirmation' => $passwordConfirmation,
                ],
                function (
                    User $user,
                    string $newPassword,
                ) use (
                    $ipAddress,
                    $userAgent,
                    $deviceInfo,
                    &$resetUser,
                ): void {
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
                        throw PasswordResetFailed::invalidOrExpired();
                    }

                    $lockedUser->password = $newPassword;
                    $lockedUser->remember_token = null;
                    $lockedUser->session_version++;
                    $lockedUser->save();

                    /*
                     * Sofortige globale Invalidierung der
                     * DB-basierten Browser-Sessions.
                     */
                    DB::table(
                        config('session.table', 'sessions')
                    )
                        ->where(
                            'user_id',
                            $lockedUser->id,
                        )
                        ->delete();

                    $this->auditWriter->write(
                        eventKey: AuditEventCatalog::AUTH_PASSWORD_RESET_COMPLETED,
                        actorType: AuditActorType::User,
                        actorUserId: $lockedUser->id,
                        subjectType: 'user',
                        subjectId: $lockedUser->id,
                        ipAddress: $ipAddress,
                        userAgent: $userAgent,
                        deviceInfo: $deviceInfo,
                    );

                    $this->auditWriter->write(
                        eventKey: AuditEventCatalog::AUTH_SESSIONS_INVALIDATED,
                        actorType: AuditActorType::User,
                        actorUserId: $lockedUser->id,
                        subjectType: 'user',
                        subjectId: $lockedUser->id,
                        newValues: [
                            'reason' => 'password_reset',
                        ],
                        ipAddress: $ipAddress,
                        userAgent: $userAgent,
                        deviceInfo: $deviceInfo,
                    );

                    $resetUser = $lockedUser;
                },
            );
        });

        if (
            $status !== Password::PASSWORD_RESET
            || ! $resetUser instanceof User
        ) {
            throw PasswordResetFailed::invalidOrExpired();
        }

        return $resetUser;
    }
}
