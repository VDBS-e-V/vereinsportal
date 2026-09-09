<?php

use App\Modules\Identity\Enums\RoleAssignmentSource;
use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\RoleAssignment;
use App\Modules\Identity\Models\User;

function makeAdministrationAccessTestUser(string $email): User
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

function grantAdministrationAccessTestRole(
    User $user,
    RoleKey $roleKey,
    $startsAt = null,
    $endsAt = null,
): void {
    $role = Role::query()->firstOrCreate(
        [
            'key' => $roleKey->value,
        ],
        [
            'name' => $roleKey->name,
            'is_system' => true,
        ],
    );

    RoleAssignment::query()->create([
        'user_id' => $user->id,
        'role_id' => $role->id,
        'source' => RoleAssignmentSource::Console,
        'starts_at' => $startsAt ?? now()->subMinute(),
        'ends_at' => $endsAt,
    ]);
}

it('redirects guests from administration to login', function () {
    $this
        ->get('http://my.vdb.test/verwaltung')
        ->assertRedirect(route('my.login'));
});

it('forbids authenticated users without an administration role', function () {
    $user = makeAdministrationAccessTestUser(
        'regular-administration-access@example.test',
    );

    $this
        ->withSession([
            'identity.session_version' => 1,
            'identity.account_validated_at' => now()->timestamp,
            'identity.two_factor_verified_at' => now()->timestamp,
        ])
        ->actingAs($user)
        ->get('http://my.vdb.test/verwaltung')
        ->assertForbidden();
});

it('allows active administration roles', function (RoleKey $roleKey) {
    $user = makeAdministrationAccessTestUser(
        $roleKey->value.'-access@example.test',
    );

    grantAdministrationAccessTestRole(
        $user,
        $roleKey,
    );

    $this
        ->withSession([
            'identity.session_version' => 1,
            'identity.account_validated_at' => now()->timestamp,
            'identity.two_factor_verified_at' => now()->timestamp,
        ])
        ->actingAs($user)
        ->get('http://my.vdb.test/verwaltung')
        ->assertOk()
        ->assertSee('Verwaltungsübersicht');
})->with([
    RoleKey::AdministrationStaff,
    RoleKey::Administration,
]);

it('rejects future and expired administration assignments', function () {
    $futureUser = makeAdministrationAccessTestUser(
        'future-administration-access@example.test',
    );

    grantAdministrationAccessTestRole(
        $futureUser,
        RoleKey::AdministrationStaff,
        now()->addDay(),
    );

    $this
        ->withSession([
            'identity.session_version' => 1,
            'identity.account_validated_at' => now()->timestamp,
            'identity.two_factor_verified_at' => now()->timestamp,
        ])
        ->actingAs($futureUser)
        ->get('http://my.vdb.test/verwaltung')
        ->assertForbidden();

    $expiredUser = makeAdministrationAccessTestUser(
        'expired-administration-access@example.test',
    );

    grantAdministrationAccessTestRole(
        $expiredUser,
        RoleKey::AdministrationStaff,
        now()->subDays(2),
        now()->subDay(),
    );

    $this
        ->withSession([
            'identity.session_version' => 1,
            'identity.account_validated_at' => now()->timestamp,
            'identity.two_factor_verified_at' => now()->timestamp,
        ])
        ->actingAs($expiredUser)
        ->get('http://my.vdb.test/verwaltung')
        ->assertForbidden();
});
