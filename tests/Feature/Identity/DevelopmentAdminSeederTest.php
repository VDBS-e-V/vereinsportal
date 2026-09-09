<?php

use App\Modules\Identity\Enums\RoleAssignmentSource;
use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\RoleAssignment;
use App\Modules\Identity\Models\User;
use Database\Seeders\DevelopmentAdminSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;

it('creates the configured local development administrator', function () {
    config()->set(
        'development.admin.email',
        'admin@vdb.test',
    );
    config()->set(
        'development.admin.default_password',
        'VdbLocalAdmin2026!',
    );

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
});

it('synchronizes the configured development admin password', function () {
    config()->set(
        'development.admin.email',
        'admin@vdb.test',
    );
    config()->set(
        'development.admin.default_password',
        'VdbLocalAdmin2026!',
    );

    $this->seed(RoleSeeder::class);
    $this->seed(DevelopmentAdminSeeder::class);

    config()->set(
        'development.admin.default_password',
        'VdbLocalAdminNeu2026!',
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
        ->toBe(1);
});
