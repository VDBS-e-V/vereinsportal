<?php

namespace Database\Seeders;

use App\Modules\Identity\Enums\RoleAssignmentSource;
use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Enums\TwoFactorMethodType;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\RoleAssignment;
use App\Modules\Identity\Models\TwoFactorMethod;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Seeder;
use InvalidArgumentException;

class DevelopmentAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment([
            'local',
            'testing',
        ])) {
            return;
        }

        $email = mb_strtolower(
            trim(
                (string) config(
                    'development.admin.email',
                )
            )
        );

        $password = (string) config(
            'development.admin.default_password',
        );

        $totpSecret = strtoupper(
            preg_replace(
                '/\s+/',
                '',
                (string) config(
                    'development.admin.totp_secret',
                ),
            ) ?? ''
        );

        if (
            $email === ''
            && $password === ''
            && $totpSecret === ''
        ) {
            return;
        }

        if (
            $email === ''
            || $password === ''
            || $totpSecret === ''
        ) {
            throw new InvalidArgumentException(
                'VDB_DEV_ADMIN_EMAIL, VDB_DEV_ADMIN_DEFAULT_PASSWORD and VDB_DEV_ADMIN_TOTP_SECRET must be configured together.',
            );
        }

        if (
            filter_var(
                $email,
                FILTER_VALIDATE_EMAIL,
            ) === false
        ) {
            throw new InvalidArgumentException(
                'VDB_DEV_ADMIN_EMAIL must be a valid email address.',
            );
        }

        if (mb_strlen($password) < 12) {
            throw new InvalidArgumentException(
                'VDB_DEV_ADMIN_DEFAULT_PASSWORD must contain at least 12 characters.',
            );
        }

        if (
            preg_match(
                '/^[A-Z2-7]{32}$/',
                $totpSecret,
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                'VDB_DEV_ADMIN_TOTP_SECRET must be a 32-character Base32 secret.',
            );
        }

        $user = User::query()->firstOrNew([
            'email' => $email,
        ]);

        $user->password = $password;
        $user->status = UserStatus::Active;
        $user->email_verified_at = now();

        if (! $user->exists) {
            $user->session_version = 1;
        }

        $user->save();

        $administrationRole = Role::query()
            ->where(
                'key',
                RoleKey::Administration->value,
            )
            ->firstOrFail();

        RoleAssignment::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'role_id' => $administrationRole->id,
                'source' => RoleAssignmentSource::Console->value,
                'source_type' => self::class,
                'source_id' => null,
                'ends_at' => null,
            ],
            [
                'starts_at' => now(),
                'comment' => 'Lokaler Entwicklungsadmin aus VDB_DEV_ADMIN_*.',
            ],
        );

        /*
         * Der lokale Test-/Entwicklungsadmin bekommt bewusst genau
         * eine deterministische TOTP-Methode. So bleibt derselbe
         * Authenticator-Eintrag auch nach erneutem Seeding gültig.
         *
         * Die Methode ist ausschließlich in local/testing aktiv;
         * das Secret kommt aus der lokalen ENV-Konfiguration.
         */
        TwoFactorMethod::query()
            ->where('user_id', $user->id)
            ->where(
                'type',
                TwoFactorMethodType::Totp,
            )
            ->delete();

        TwoFactorMethod::query()->create([
            'user_id' => $user->id,
            'type' => TwoFactorMethodType::Totp,
            'secret' => $totpSecret,
            'confirmed_at' => now(),
            'disabled_at' => null,
        ]);
    }
}
