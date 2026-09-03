<?php

use App\Modules\Identity\Enums\RoleAssignmentSource;
use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Enums\TwoFactorMethodType;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\RoleAssignment;
use App\Modules\Identity\Models\TwoFactorMethod;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\TwoFactorRequirement;

function makeTwoFactorRequirementUser(): User
{
    $user = User::query()->create([
        'email' => fake()->unique()->safeEmail(),
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    $user->email_verified_at = now();
    $user->save();

    return $user->refresh();
}

it('does not require two factor for a normal user without an enabled method', function () {
    $user = makeTwoFactorRequirementUser();

    expect(
        app(TwoFactorRequirement::class)
            ->requiresChallenge($user)
    )->toBeFalse();
});

it('requires two factor when a voluntary method is enabled', function () {
    $user = makeTwoFactorRequirementUser();

    TwoFactorMethod::query()->create([
        'user_id' => $user->id,
        'type' => TwoFactorMethodType::Email,
        'confirmed_at' => now(),
    ]);

    expect(
        app(TwoFactorRequirement::class)
            ->requiresChallenge($user)
    )->toBeTrue();
});

it('requires two factor for an active board role', function () {
    $user = makeTwoFactorRequirementUser();

    $role = Role::query()->create([
        'key' => RoleKey::BoardMember,
        'name' => 'Vorstandsmitglied',
        'is_system' => true,
    ]);

    RoleAssignment::query()->create([
        'user_id' => $user->id,
        'role_id' => $role->id,
        'source' =>
            RoleAssignmentSource::Automatic,
        'starts_at' => now()->subDay(),
    ]);

    $requirement =
        app(TwoFactorRequirement::class);

    expect(
        $requirement->isRequiredByRole($user)
    )
        ->toBeTrue()
        ->and(
            $requirement->canUseEmail($user)
        )
        ->toBeTrue()
        ->and(
            $requirement->requiresChallenge(
                $user
            )
        )
        ->toBeTrue();
});

it('ignores an ended mandatory role assignment', function () {
    $user = makeTwoFactorRequirementUser();

    $role = Role::query()->create([
        'key' => RoleKey::Administration,
        'name' => 'Administration',
        'is_system' => true,
    ]);

    RoleAssignment::query()->create([
        'user_id' => $user->id,
        'role_id' => $role->id,
        'source' =>
            RoleAssignmentSource::Console,
        'starts_at' =>
            now()->subDays(5),
        'ends_at' =>
            now()->subDay(),
    ]);

    expect(
        app(TwoFactorRequirement::class)
            ->isRequiredByRole($user)
    )->toBeFalse();
});