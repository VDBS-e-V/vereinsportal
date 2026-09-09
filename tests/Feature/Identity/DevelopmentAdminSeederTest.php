<?php

use App\Modules\Identity\Enums\RoleAssignmentSource;
use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Enums\TwoFactorMethodType;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\RoleAssignment;
use App\Modules\Identity\Models\TwoFactorMethod;
use App\Modules\Identity\Models\User;
use Database\Seeders\DevelopmentAdminSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;

function configureDevelopmentAdmin(
    string $password = 'VdbLocalAdmin2026!',
    string $totpSecret = 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP',
): void {
    config()->set(
        'development.admin.email',
        'admin@vdb.test',
    );
    config()->set(
        'development.admin.default_password',
        $password,
    );
    config()->set(
        'development.admin.totp_secret',
        $totpSecret,
    );
}

it('creates the configured local development administrator', function () {
    configureDevelopmentAdmin();

    $this->seed(RoleSeeder::class);
    $this->seed(DevelopmentAdminSeeder::class);

    $user = User::query()
        ->where('email', 'admin@vdb.test')
        ->firstOrFail();

    expect($user->status)
        ->toBe(UserStatus::Active)
        ->and($user->email_verified_at)
        ->not->toBeNull()
        ->and(
            Hash::check(
                'VdbLocalAdmin2026!',
                $user->password,
            )
        )
        ->toBeTrue();

    $role = Role::query()
        ->where(
            'key',
            RoleKey::Administration->value,
        )
        ->firstOrFail();

    $assignment = RoleAssignment::query()
        ->where('user_id', $user->id)
        ->where('role_id', $role->id)
        ->firstOrFail();

    expect($assignment->source)
        ->toBe(RoleAssignmentSource::Console)
        ->and($assignment->ends_at)
        ->toBeNull();

    $totpMethod = TwoFactorMethod::query()
        ->where('user_id', $user->id)
        ->where(
            'type',
            TwoFactorMethodType::Totp,
        )
        ->sole();

    expect($totpMethod->secret)
        ->toBe('JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP')
        ->and($totpMethod->confirmed_at)
        ->not->toBeNull()
        ->and($totpMethod->disabled_at)
        ->toBeNull();
});

it('synchronizes the configured development admin password and totp secret', function () {
    configureDevelopmentAdmin();

    $this->seed(RoleSeeder::class);
    $this->seed(DevelopmentAdminSeeder::class);

    configureDevelopmentAdmin(
        password: 'VdbLocalAdminNeu2026!',
        totpSecret: 'KRSXG5DSNFXGOIDBNZQW4ZBANFZSAYJA',
    );

    $this->seed(DevelopmentAdminSeeder::class);

    $user = User::query()
        ->where('email', 'admin@vdb.test')
        ->firstOrFail();

    expect(
        Hash::check(
            'VdbLocalAdminNeu2026!',
            $user->password,
        )
    )->toBeTrue()
        ->and(
            RoleAssignment::query()
                ->where('user_id', $user->id)
                ->count()
        )
        ->toBe(1)
        ->and(
            TwoFactorMethod::query()
                ->where('user_id', $user->id)
                ->where(
                    'type',
                    TwoFactorMethodType::Totp,
                )
                ->count()
        )
        ->toBe(1);

    $totpMethod = TwoFactorMethod::query()
        ->where('user_id', $user->id)
        ->where(
            'type',
            TwoFactorMethodType::Totp,
        )
        ->sole();

    expect($totpMethod->secret)
        ->toBe('KRSXG5DSNFXGOIDBNZQW4ZBANFZSAYJA')
        ->and($totpMethod->confirmed_at)
        ->not->toBeNull();
});

it('rejects an invalid configured development admin totp secret', function () {
    configureDevelopmentAdmin(
        totpSecret: 'not-a-base32-secret',
    );

    $this->seed(RoleSeeder::class);

    expect(
        fn () => $this->seed(
            DevelopmentAdminSeeder::class
        )
    )->toThrow(
        InvalidArgumentException::class,
        'VDB_DEV_ADMIN_TOTP_SECRET must be a 32-character Base32 secret.',
    );
});
