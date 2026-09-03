<?php

namespace App\Modules\Identity\Actions\Auth;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\PasswordRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

final class ChangePasswordAction
{
    public function __construct(
        private readonly AuditWriter $auditWriter,
    ) {
    }

    public function execute(
        User $user,
        string $currentPassword,
        string $password,
        string $passwordConfirmation,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $deviceInfo = null,
    ): User {
        Validator::make(
            [
                'current_password' => $currentPassword,
                'password' => $password,
                'password_confirmation' =>
                    $passwordConfirmation,
            ],
            [
                'current_password' => [
                    'required',
                    'string',
                ],
                'password' => [
                    'required',
                    'string',
                    'confirmed',
                    PasswordRules::default(),
                ],
            ],
        )->validate();

        return DB::transaction(function () use (
            $user,
            $currentPassword,
            $password,
            $ipAddress,
            $userAgent,
            $deviceInfo,
        ): User {
            $lockedUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                ! Hash::check(
                    $currentPassword,
                    $lockedUser->password,
                )
            ) {
                Validator::make(
                    [],
                    [],
                )->after(function ($validator): void {
                    $validator->errors()->add(
                        'current_password',
                        'Das aktuelle Passwort ist nicht korrekt.',
                    );
                })->validate();
            }

            $lockedUser->password = $password;
            $lockedUser->save();

            $this->auditWriter->write(
                eventKey:
                    AuditEventCatalog::AUTH_PASSWORD_CHANGED,
                actorType: AuditActorType::User,
                actorUserId: $lockedUser->id,
                subjectType: 'user',
                subjectId: $lockedUser->id,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                deviceInfo: $deviceInfo,
            );

            /*
             * Bewusst:
             * - session_version unverändert
             * - remember_token unverändert
             * - andere Sessions bleiben aktiv
             */
            return $lockedUser;
        });
    }
}