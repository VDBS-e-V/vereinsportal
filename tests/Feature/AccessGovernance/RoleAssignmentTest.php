<?php

use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Access\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Carbon;

it('stores an automatic role assignment with its relationships', function () {
    $this->seed(RoleSeeder::class);

    $user = User::factory()->create();

    $role = Role::query()
        ->where('key', RoleKey::Member->value)
        ->firstOrFail();

    $assignment = RoleAssignment::query()->create([
        'user_id' => $user->id,
        'role_id' => $role->id,
        'source' => RoleAssignmentSource::Automatic,
        'source_type' => 'account',
        'source_id' => $user->id,
        'starts_at' => now(),
    ]);

    expect($assignment->source)->toBe(RoleAssignmentSource::Automatic)
        ->and($assignment->starts_at)->toBeInstanceOf(Carbon::class)
        ->and($assignment->ends_at)->toBeNull()
        ->and($assignment->user->is($user))->toBeTrue()
        ->and($assignment->role->is($role))->toBeTrue()
        ->and($user->roleAssignments->pluck('id'))->toContain($assignment->id);
});

it('can store the actor of a manual role assignment', function () {
    $this->seed(RoleSeeder::class);

    $target = User::factory()->create();
    $actor = User::factory()->create();

    $role = Role::query()
        ->where('key', RoleKey::BoardMember->value)
        ->firstOrFail();

    $assignment = RoleAssignment::query()->create([
        'user_id' => $target->id,
        'role_id' => $role->id,
        'source' => RoleAssignmentSource::Manual,
        'granted_by_user_id' => $actor->id,
        'starts_at' => now(),
        'comment' => 'Durch Vorstandsbeschluss.',
    ]);

    expect($assignment->grantedBy->is($actor))->toBeTrue();
});

it('can end a role assignment', function () {
    $this->seed(RoleSeeder::class);

    $user = User::factory()->create();
    $role = Role::query()->where('key', RoleKey::Team->value)->firstOrFail();

    $assignment = RoleAssignment::query()->create([
        'user_id' => $user->id,
        'role_id' => $role->id,
        'source' => RoleAssignmentSource::Manual,
        'starts_at' => now()->subYear(),
    ]);

    $assignment->update(['ends_at' => now()]);

    expect($assignment->ends_at)->not->toBeNull();
});
