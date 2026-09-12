<?php

use App\Modules\Identity\Enums\RoleAssignmentSource;
use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\RoleAssignment;
use App\Modules\Identity\Models\User;

function makeSystemManagedMemberRoleUser(string $email): User
{
    $user = User::query()->create([
        'email' => $email,
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 1,
    ]);
    $user->email_verified_at = now();
    $user->save();

    return $user->refresh();
}

function giveSystemManagedMemberRoleAdministration(User $user): void
{
    $role = Role::query()->firstOrCreate(
        ['key' => RoleKey::Administration->value],
        [
            'name' => 'Administration',
            'is_system' => true,
        ],
    );

    RoleAssignment::query()->create([
        'user_id' => $user->id,
        'role_id' => $role->id,
        'source' => RoleAssignmentSource::Console,
        'starts_at' => now()->subMinute(),
    ]);
}

function systemManagedMemberRoleSession(): array
{
    return [
        'identity.session_version' => 1,
        'identity.account_validated_at' => now()->timestamp,
        'identity.two_factor_verified_at' => now()->timestamp,
    ];
}

it('does not offer or allow manual assignment of the member role', function () {
    $admin = makeSystemManagedMemberRoleUser('member-role-admin@example.test');
    giveSystemManagedMemberRoleAdministration($admin);
    $target = makeSystemManagedMemberRoleUser('member-role-target@example.test');

    $memberRole = Role::query()->firstOrCreate(
        ['key' => RoleKey::Member->value],
        [
            'name' => 'Mitglied (System)',
            'is_system' => true,
        ],
    );

    $this
        ->withSession(systemManagedMemberRoleSession())
        ->actingAs($admin)
        ->get('http://my.vdb.test/verwaltung/benutzer/'.$target->id)
        ->assertOk()
        ->assertDontSee('Mitglied (System)');

    $this
        ->withSession(systemManagedMemberRoleSession())
        ->actingAs($admin)
        ->post(
            'http://my.vdb.test/verwaltung/benutzer/'.$target->id.'/rollen',
            [
                'role_key' => RoleKey::Member->value,
                'role_comment' => 'Darf nicht manuell vergeben werden',
            ],
        )
        ->assertRedirect(route('administration.users.show', $target))
        ->assertSessionHas('status_type', 'danger');

    expect(
        RoleAssignment::query()
            ->where('user_id', $target->id)
            ->where('role_id', $memberRole->id)
            ->exists(),
    )->toBeFalse();
});

it('does not allow a legacy manual member assignment to be ended in user management', function () {
    $admin = makeSystemManagedMemberRoleUser('member-role-end-admin@example.test');
    giveSystemManagedMemberRoleAdministration($admin);
    $target = makeSystemManagedMemberRoleUser('member-role-end-target@example.test');

    $memberRole = Role::query()->firstOrCreate(
        ['key' => RoleKey::Member->value],
        [
            'name' => 'Mitglied (System)',
            'is_system' => true,
        ],
    );

    $assignment = RoleAssignment::query()->create([
        'user_id' => $target->id,
        'role_id' => $memberRole->id,
        'source' => RoleAssignmentSource::Manual,
        'starts_at' => now()->subMinute(),
        'granted_by_user_id' => $admin->id,
        'comment' => 'Legacy-Testdatensatz',
    ]);

    $this
        ->withSession(systemManagedMemberRoleSession())
        ->actingAs($admin)
        ->post(
            'http://my.vdb.test/verwaltung/benutzer/'.$target->id.'/rollen/'.$assignment->id.'/beenden',
            [
                'end_comment' => 'Darf nicht manuell beendet werden',
            ],
        )
        ->assertRedirect(route('administration.users.show', $target))
        ->assertSessionHas('status_type', 'danger');

    expect($assignment->refresh()->ends_at)->toBeNull();
});
