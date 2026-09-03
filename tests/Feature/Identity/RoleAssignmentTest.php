<?php

use App\Modules\Identity\Enums\RoleAssignmentSource;
use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\RoleAssignment;
use App\Modules\Identity\Models\User;
use Database\Seeders\RoleSeeder;

it('stores an automatic role assignment with its relationships', function () {
    $this->seed(RoleSeeder::class);

    $user = User::query()->create([
        'email' => 'roles@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    $role = Role::query()
        ->where('key', RoleKey::Guest->value)
        ->firstOrFail();

    $assignment = RoleAssignment::query()->create([
        'user_id' => $user->id,
        'role_id' => $role->id,
        'source' => RoleAssignmentSource::Automatic,
        'source_type' => 'account',
        'source_id' => $user->id,
        'starts_at' => now(),
    ]);

    expect($assignment->source)
        ->toBe(RoleAssignmentSource::Automatic)
        ->and($assignment->starts_at)
        ->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($assignment->ends_at)
        ->toBeNull()
        ->and($assignment->user->is($user))
        ->toBeTrue()
        ->and($assignment->role->is($role))
        ->toBeTrue();
});

it('can store the actor of a manual role assignment', function () {
    $this->seed(RoleSeeder::class);

    $user = User::query()->create([
        'email' => 'target@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    $actor = User::query()->create([
        'email' => 'admin@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    $role = Role::query()
        ->where('key', RoleKey::Team->value)
        ->firstOrFail();

    $assignment = RoleAssignment::query()->create([
        'user_id' => $user->id,
        'role_id' => $role->id,
        'source' => RoleAssignmentSource::Manual,
        'starts_at' => now(),
        'granted_by_user_id' => $actor->id,
        'comment' => 'Manuelle Zuweisung',
    ]);

    expect($assignment->grantedBy->is($actor))->toBeTrue()
        ->and($assignment->source)
        ->toBe(RoleAssignmentSource::Manual);
});

it('keeps historical role assignments instead of overwriting them', function () {
    $this->seed(RoleSeeder::class);

    $user = User::query()->create([
        'email' => 'history-role@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    $role = Role::query()
        ->where('key', RoleKey::Member->value)
        ->firstOrFail();

    RoleAssignment::query()->create([
        'user_id' => $user->id,
        'role_id' => $role->id,
        'source' => RoleAssignmentSource::Automatic,
        'source_type' => 'membership',
        'source_id' => 1,
        'starts_at' => now()->subYear(),
        'ends_at' => now()->subMonths(6),
    ]);

    RoleAssignment::query()->create([
        'user_id' => $user->id,
        'role_id' => $role->id,
        'source' => RoleAssignmentSource::Automatic,
        'source_type' => 'membership',
        'source_id' => 2,
        'starts_at' => now(),
    ]);

    expect(
        RoleAssignment::query()
            ->where('user_id', $user->id)
            ->where('role_id', $role->id)
            ->count()
    )->toBe(2);
});