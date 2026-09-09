<?php

namespace Database\Seeders;

use App\Modules\Identity\Enums\RoleAssignmentSource;
use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\RoleAssignment;
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

        if ($email === '' || $password === '') {
            return;
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
                'source' =>
                    RoleAssignmentSource::Console->value,
                'source_type' => self::class,
                'source_id' => null,
                'ends_at' => null,
            ],
            [
                'starts_at' => now(),
                'comment' =>
                    'Lokaler Entwicklungsadmin aus VDB_DEV_ADMIN_*.',
            ],
        );
    }
}
