<?php

use App\Modules\Administration\Enums\AdministrationCapability;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Identity\Enums\RoleAssignmentSource;
use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\RoleAssignment;
use App\Modules\Identity\Models\User;

function makeAdministrationAccessTestUser(
    string $email,
    UserStatus $status = UserStatus::Active,
    bool $verified = true,
): User {
    $user = User::query()->create([
        'email' => $email,
        'password' => 'Sicher123!',
        'status' => $status,
        'session_version' => 1,
    ]);

    $user->email_verified_at = $verified ? now() : null;
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

function administrationAccessTestSession(): array
{
    return [
        'identity.session_version' => 1,
        'identity.account_validated_at' => now()->timestamp,
        'identity.two_factor_verified_at' => now()->timestamp,
    ];
}

function administrationCapabilityValues(User $user): array
{
    $values = array_map(
        static fn (AdministrationCapability $capability): string => $capability->value,
        app(AdministrationAccess::class)->capabilities($user),
    );
    sort($values);

    return $values;
}

it('redirects guests from all internal staff areas to login', function () {
    foreach (['verwaltung', 'vorstand', 'koordination'] as $path) {
        $this
            ->get("http://my.vdb.test/{$path}")
            ->assertRedirect(route('my.login'));
    }
});

it('forbids authenticated users without an internal staff role', function () {
    $user = makeAdministrationAccessTestUser(
        'regular-administration-access@example.test',
    );

    foreach (['verwaltung', 'vorstand', 'koordination'] as $path) {
        $this
            ->withSession(administrationAccessTestSession())
            ->actingAs($user)
            ->get("http://my.vdb.test/{$path}")
            ->assertForbidden();
    }
});

it('maps administration staff to the administration area and its responsibilities', function () {
    $user = makeAdministrationAccessTestUser(
        'administration-staff-capabilities@example.test',
    );
    grantAdministrationAccessTestRole(
        $user,
        RoleKey::AdministrationStaff,
    );

    $expected = [
        AdministrationCapability::AdministrationAreaAccess->value,
        AdministrationCapability::CommunicationManage->value,
        AdministrationCapability::CommunicationRead->value,
        AdministrationCapability::PersonsManage->value,
        AdministrationCapability::PersonsRead->value,
        AdministrationCapability::PortalInvitationsManage->value,
        AdministrationCapability::UsersRead->value,
        AdministrationCapability::UserStatusManage->value,
    ];
    sort($expected);

    expect(administrationCapabilityValues($user))->toBe($expected);

    $access = app(AdministrationAccess::class);

    expect($access->allows($user))->toBeTrue()
        ->and($access->allowsCapability(
            $user,
            AdministrationCapability::AdministrationAreaAccess,
        ))->toBeTrue()
        ->and($access->allowsCapability(
            $user,
            AdministrationCapability::BoardAreaAccess,
        ))->toBeFalse()
        ->and($access->allowsCapability(
            $user,
            AdministrationCapability::CoordinationAreaAccess,
        ))->toBeFalse()
        ->and($access->allowsCapability(
            $user,
            AdministrationCapability::MembershipsRead,
        ))->toBeFalse()
        ->and($access->allowsCapability(
            $user,
            AdministrationCapability::RolesManage,
        ))->toBeFalse()
        ->and($access->allowsCapability(
            $user,
            AdministrationCapability::AuditRead,
        ))->toBeFalse();
});

it('maps board members to the board area and membership responsibilities only', function () {
    $user = makeAdministrationAccessTestUser(
        'board-member-capabilities@example.test',
    );
    grantAdministrationAccessTestRole(
        $user,
        RoleKey::BoardMember,
    );

    $expected = [
        AdministrationCapability::BoardAreaAccess->value,
        AdministrationCapability::MembershipConsentsManage->value,
        AdministrationCapability::MembershipConsentsRead->value,
        AdministrationCapability::MembershipDocumentsManage->value,
        AdministrationCapability::MembershipDocumentsRead->value,
        AdministrationCapability::MembershipsManage->value,
        AdministrationCapability::MembershipsRead->value,
    ];
    sort($expected);

    expect(administrationCapabilityValues($user))->toBe($expected);

    $access = app(AdministrationAccess::class);

    expect($access->allows($user))->toBeTrue()
        ->and($access->allowsCapability(
            $user,
            AdministrationCapability::BoardAreaAccess,
        ))->toBeTrue()
        ->and($access->allowsCapability(
            $user,
            AdministrationCapability::AdministrationAreaAccess,
        ))->toBeFalse()
        ->and($access->allowsCapability(
            $user,
            AdministrationCapability::CoordinationAreaAccess,
        ))->toBeFalse()
        ->and($access->allowsCapability(
            $user,
            AdministrationCapability::PersonsRead,
        ))->toBeFalse()
        ->and($access->allowsCapability(
            $user,
            AdministrationCapability::PortalInvitationsManage,
        ))->toBeFalse()
        ->and($access->allowsCapability(
            $user,
            AdministrationCapability::UsersRead,
        ))->toBeFalse()
        ->and($access->allowsCapability(
            $user,
            AdministrationCapability::CommunicationRead,
        ))->toBeFalse()
        ->and($access->allowsCapability(
            $user,
            AdministrationCapability::RolesManage,
        ))->toBeFalse()
        ->and($access->allowsCapability(
            $user,
            AdministrationCapability::AuditRead,
        ))->toBeFalse();
});

it('maps coordination roles to their own area without unrelated fach capabilities', function (RoleKey $roleKey) {
    $user = makeAdministrationAccessTestUser(
        $roleKey->value.'-coordination-area@example.test',
    );
    grantAdministrationAccessTestRole($user, $roleKey);

    expect(administrationCapabilityValues($user))->toBe([
        AdministrationCapability::CoordinationAreaAccess->value,
    ]);

    $access = app(AdministrationAccess::class);

    expect($access->allows($user))->toBeTrue()
        ->and($access->allowsCapability(
            $user,
            AdministrationCapability::CoordinationAreaAccess,
        ))->toBeTrue()
        ->and($access->allowsCapability(
            $user,
            AdministrationCapability::AdministrationAreaAccess,
        ))->toBeFalse()
        ->and($access->allowsCapability(
            $user,
            AdministrationCapability::BoardAreaAccess,
        ))->toBeFalse();
})->with([
    RoleKey::Coordination,
    RoleKey::EducationCoordination,
]);

it('maps administration to every beta capability and every internal area', function () {
    $user = makeAdministrationAccessTestUser(
        'administration-capabilities@example.test',
    );
    grantAdministrationAccessTestRole(
        $user,
        RoleKey::Administration,
    );

    $expected = array_map(
        static fn (AdministrationCapability $capability): string => $capability->value,
        AdministrationCapability::cases(),
    );
    sort($expected);

    expect(administrationCapabilityValues($user))->toBe($expected);
});

it('does not grant internal staff capabilities to unrelated roles', function (RoleKey $roleKey) {
    $user = makeAdministrationAccessTestUser(
        $roleKey->value.'-no-administration-capabilities@example.test',
    );
    grantAdministrationAccessTestRole($user, $roleKey);

    $access = app(AdministrationAccess::class);

    expect($access->allows($user))->toBeFalse()
        ->and($access->capabilities($user))->toBe([]);
})->with([
    RoleKey::Guest,
    RoleKey::Member,
    RoleKey::Team,
]);

it('keeps administration staff inside the administration area', function () {
    $staff = makeAdministrationAccessTestUser(
        'administration-staff-routes@example.test',
    );
    grantAdministrationAccessTestRole(
        $staff,
        RoleKey::AdministrationStaff,
    );

    $client = $this
        ->withSession(administrationAccessTestSession())
        ->actingAs($staff);

    $client->get('http://my.vdb.test/verwaltung')->assertOk();
    $client->get('http://my.vdb.test/verwaltung/personen')->assertOk();
    $client->get('http://my.vdb.test/verwaltung/personen/anlegen')->assertOk();
    $client->get('http://my.vdb.test/verwaltung/benutzer')->assertOk();
    $client->get('http://my.vdb.test/verwaltung/kommunikation/vorlagen')->assertOk();

    $client->get('http://my.vdb.test/vorstand')->assertForbidden();
    $client->get('http://my.vdb.test/vorstand/mitgliedschaften')->assertForbidden();
    $client->get('http://my.vdb.test/koordination')->assertForbidden();
    $client->get('http://my.vdb.test/verwaltung/audit')->assertForbidden();
    $client->get('http://my.vdb.test/verwaltung/mitgliedschaften')->assertNotFound();
});

it('keeps board members inside the board area and moves membership urls there', function () {
    $board = makeAdministrationAccessTestUser(
        'board-member-routes@example.test',
    );
    grantAdministrationAccessTestRole(
        $board,
        RoleKey::BoardMember,
    );

    $client = $this
        ->withSession(administrationAccessTestSession())
        ->actingAs($board);

    $client->get('http://my.vdb.test/vorstand')->assertOk();
    $client->get('http://my.vdb.test/vorstand/mitgliedschaften')->assertOk();

    $client->get('http://my.vdb.test/verwaltung')->assertForbidden();
    $client->get('http://my.vdb.test/verwaltung/personen')->assertForbidden();
    $client->get('http://my.vdb.test/verwaltung/benutzer')->assertForbidden();
    $client->get('http://my.vdb.test/verwaltung/kommunikation/vorlagen')->assertForbidden();
    $client->get('http://my.vdb.test/koordination')->assertForbidden();
    $client->get('http://my.vdb.test/verwaltung/mitgliedschaften')->assertNotFound();
});

it('keeps coordination roles inside the coordination area', function (RoleKey $roleKey) {
    $coordinator = makeAdministrationAccessTestUser(
        $roleKey->value.'-routes@example.test',
    );
    grantAdministrationAccessTestRole($coordinator, $roleKey);

    $client = $this
        ->withSession(administrationAccessTestSession())
        ->actingAs($coordinator);

    $client->get('http://my.vdb.test/koordination')->assertOk();
    $client->get('http://my.vdb.test/verwaltung')->assertForbidden();
    $client->get('http://my.vdb.test/vorstand')->assertForbidden();
})->with([
    RoleKey::Coordination,
    RoleKey::EducationCoordination,
]);

it('allows full administration to enter every separated area', function () {
    $admin = makeAdministrationAccessTestUser(
        'administration-routes@example.test',
    );
    grantAdministrationAccessTestRole(
        $admin,
        RoleKey::Administration,
    );

    $client = $this
        ->withSession(administrationAccessTestSession())
        ->actingAs($admin);

    $client->get('http://my.vdb.test/verwaltung')->assertOk();
    $client->get('http://my.vdb.test/verwaltung/personen/anlegen')->assertOk();
    $client->get('http://my.vdb.test/verwaltung/audit')->assertOk();
    $client->get('http://my.vdb.test/vorstand')->assertOk();
    $client->get('http://my.vdb.test/vorstand/mitgliedschaften')->assertOk();
    $client->get('http://my.vdb.test/koordination')->assertOk();
});

it('rejects future and expired internal staff assignments', function () {
    $futureUser = makeAdministrationAccessTestUser(
        'future-administration-access@example.test',
    );

    grantAdministrationAccessTestRole(
        $futureUser,
        RoleKey::AdministrationStaff,
        now()->addDay(),
    );

    expect(app(AdministrationAccess::class)->capabilities($futureUser))->toBe([]);

    $this
        ->withSession(administrationAccessTestSession())
        ->actingAs($futureUser)
        ->get('http://my.vdb.test/verwaltung')
        ->assertForbidden();

    $expiredUser = makeAdministrationAccessTestUser(
        'expired-administration-access@example.test',
    );

    grantAdministrationAccessTestRole(
        $expiredUser,
        RoleKey::BoardMember,
        now()->subDays(2),
        now()->subDay(),
    );

    expect(app(AdministrationAccess::class)->capabilities($expiredUser))->toBe([]);

    $this
        ->withSession(administrationAccessTestSession())
        ->actingAs($expiredUser)
        ->get('http://my.vdb.test/vorstand')
        ->assertForbidden();
});

it('rejects inactive or unverified users even with an administration role', function () {
    $disabled = makeAdministrationAccessTestUser(
        'disabled-administration-access@example.test',
        UserStatus::Disabled,
    );
    grantAdministrationAccessTestRole(
        $disabled,
        RoleKey::Administration,
    );

    $unverified = makeAdministrationAccessTestUser(
        'unverified-administration-access@example.test',
        UserStatus::Active,
        false,
    );
    grantAdministrationAccessTestRole(
        $unverified,
        RoleKey::Administration,
    );

    $access = app(AdministrationAccess::class);

    expect($access->allows($disabled))->toBeFalse()
        ->and($access->capabilities($disabled))->toBe([])
        ->and($access->allows($unverified))->toBeFalse()
        ->and($access->capabilities($unverified))->toBe([]);
});
