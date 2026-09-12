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

it('redirects guests from administration to login', function () {
    $this
        ->get('http://my.vdb.test/verwaltung')
        ->assertRedirect(route('my.login'));
});

it('forbids authenticated users without an administration capability role', function () {
    $user = makeAdministrationAccessTestUser(
        'regular-administration-access@example.test',
    );

    $this
        ->withSession(administrationAccessTestSession())
        ->actingAs($user)
        ->get('http://my.vdb.test/verwaltung')
        ->assertForbidden();
});

it('maps administration staff to data account and communication responsibilities', function () {
    $user = makeAdministrationAccessTestUser(
        'administration-staff-capabilities@example.test',
    );
    grantAdministrationAccessTestRole(
        $user,
        RoleKey::AdministrationStaff,
    );

    $expected = [
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
            AdministrationCapability::MembershipsRead,
        ))->toBeFalse()
        ->and($access->allowsCapability(
            $user,
            AdministrationCapability::MembershipsManage,
        ))->toBeFalse()
        ->and($access->allowsCapability(
            $user,
            AdministrationCapability::MembershipDocumentsRead,
        ))->toBeFalse()
        ->and($access->allowsCapability(
            $user,
            AdministrationCapability::MembershipConsentsRead,
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

it('maps board members to membership responsibilities only', function () {
    $user = makeAdministrationAccessTestUser(
        'board-member-capabilities@example.test',
    );
    grantAdministrationAccessTestRole(
        $user,
        RoleKey::BoardMember,
    );

    $expected = [
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

it('maps administration to every beta capability', function () {
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

it('does not grant administration capabilities to roles without implemented administration modules', function (RoleKey $roleKey) {
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
    RoleKey::EducationCoordination,
    RoleKey::Coordination,
]);

it('keeps administration staff inside data account and communication routes', function () {
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

    $client->get('http://my.vdb.test/verwaltung/mitgliedschaften')->assertForbidden();
    $client->get('http://my.vdb.test/verwaltung/audit')->assertForbidden();
});

it('keeps board members inside membership routes', function () {
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

    $client->get('http://my.vdb.test/verwaltung')->assertOk();
    $client->get('http://my.vdb.test/verwaltung/mitgliedschaften')->assertOk();

    $client->get('http://my.vdb.test/verwaltung/personen')->assertForbidden();
    $client->get('http://my.vdb.test/verwaltung/benutzer')->assertForbidden();
    $client->get('http://my.vdb.test/verwaltung/kommunikation/vorlagen')->assertForbidden();
    $client->get('http://my.vdb.test/verwaltung/audit')->assertForbidden();
});

it('allows administration capability-protected management and audit routes', function () {
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
    $client->get('http://my.vdb.test/verwaltung/mitgliedschaften')->assertOk();
    $client->get('http://my.vdb.test/verwaltung/audit')->assertOk();
});

it('rejects future and expired administration assignments', function () {
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
        ->get('http://my.vdb.test/verwaltung')
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
