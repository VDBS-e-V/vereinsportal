<?php

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Enums\RoleAssignmentSource;
use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\RoleAssignment;
use App\Modules\Identity\Models\User;

function makeAdministrationManagementUser(
    string $email,
    UserStatus $status = UserStatus::Active,
    bool $verified = true,
    int $sessionVersion = 1,
): User {
    $user = User::query()->create([
        'email' => $email,
        'password' => 'Sicher123!',
        'status' => $status,
        'session_version' => $sessionVersion,
    ]);

    if ($verified) {
        $user->email_verified_at = now();
        $user->save();
    }

    return $user->refresh();
}

function giveAdministrationManagementRole(
    User $user,
    RoleKey $roleKey,
    RoleAssignmentSource $source = RoleAssignmentSource::Console,
): RoleAssignment {
    $role = Role::query()->updateOrCreate(
        [
            'key' => $roleKey->value,
        ],
        [
            'name' => $roleKey->name,
            'is_system' => true,
        ],
    );

    return RoleAssignment::query()->create([
        'user_id' => $user->id,
        'role_id' => $role->id,
        'source' => $source,
        'starts_at' => now()->subMinute(),
    ]);
}

function administrationManagementSession(
    User $user,
): array {
    return [
        'identity.session_version' => $user->session_version,
        'identity.account_validated_at' => now()->timestamp,
        'identity.two_factor_verified_at' => now()->timestamp,
    ];
}

it('keeps administration staff read only', function () {
    $staff = makeAdministrationManagementUser(
        'staff-management@example.test'
    );
    giveAdministrationManagementRole(
        $staff,
        RoleKey::AdministrationStaff,
    );

    $target = makeAdministrationManagementUser(
        'staff-target@example.test'
    );

    $this
        ->withSession(
            administrationManagementSession($staff)
        )
        ->actingAs($staff)
        ->post(
            'http://my.vdb.test/verwaltung/benutzer/'.
            $target->id.
            '/status',
            [
                'status_action' => 'disable',
                'status_comment' => 'Testweise Sperrung',
            ],
        )
        ->assertForbidden();

    expect($target->refresh()->status)
        ->toBe(UserStatus::Active);
});

it('assigns a manual role and audits the action', function () {
    $admin = makeAdministrationManagementUser(
        'role-admin@example.test'
    );
    giveAdministrationManagementRole(
        $admin,
        RoleKey::Administration,
    );

    $target = makeAdministrationManagementUser(
        'role-target@example.test'
    );

    Role::query()->updateOrCreate(
        [
            'key' => RoleKey::Team->value,
        ],
        [
            'name' => 'Teamende',
            'is_system' => true,
        ],
    );

    $this
        ->withSession(
            administrationManagementSession($admin)
        )
        ->actingAs($admin)
        ->post(
            'http://my.vdb.test/verwaltung/benutzer/'.
            $target->id.
            '/rollen',
            [
                'role_key' => RoleKey::Team->value,
                'role_comment' => 'Mitarbeit im Projektteam',
            ],
        )
        ->assertRedirect(
            route(
                'administration.users.show',
                $target,
            )
        )
        ->assertSessionHas(
            'status_type',
            'success',
        );

    $assignment = RoleAssignment::query()
        ->where('user_id', $target->id)
        ->whereHas(
            'role',
            fn ($query) => $query->where(
                'key',
                RoleKey::Team->value,
            ),
        )
        ->sole();

    expect($assignment->source)
        ->toBe(RoleAssignmentSource::Manual)
        ->and($assignment->granted_by_user_id)
        ->toBe($admin->id)
        ->and($assignment->comment)
        ->toBe('Mitarbeit im Projektteam')
        ->and(
            AuditEvent::query()
                ->where(
                    'event_key',
                    AuditEventCatalog::ROLE_MANUAL_ASSIGNED,
                )
                ->where(
                    'actor_user_id',
                    $admin->id,
                )
                ->count()
        )
        ->toBe(1);
});

it('does not create a duplicate active role assignment', function () {
    $admin = makeAdministrationManagementUser(
        'duplicate-admin@example.test'
    );
    giveAdministrationManagementRole(
        $admin,
        RoleKey::Administration,
    );

    $target = makeAdministrationManagementUser(
        'duplicate-target@example.test'
    );
    giveAdministrationManagementRole(
        $target,
        RoleKey::Team,
        RoleAssignmentSource::Automatic,
    );

    $this
        ->withSession(
            administrationManagementSession($admin)
        )
        ->actingAs($admin)
        ->post(
            'http://my.vdb.test/verwaltung/benutzer/'.
            $target->id.
            '/rollen',
            [
                'role_key' => RoleKey::Team->value,
                'role_comment' => 'Darf nicht doppelt werden',
            ],
        )
        ->assertRedirect()
        ->assertSessionHas(
            'status_type',
            'danger',
        );

    expect(
        RoleAssignment::query()
            ->where('user_id', $target->id)
            ->count()
    )->toBe(1);
});

it('ends active manual assignments but leaves automatic assignments protected', function () {
    $admin = makeAdministrationManagementUser(
        'end-role-admin@example.test'
    );
    giveAdministrationManagementRole(
        $admin,
        RoleKey::Administration,
    );

    $target = makeAdministrationManagementUser(
        'end-role-target@example.test'
    );

    $manual = giveAdministrationManagementRole(
        $target,
        RoleKey::Coordination,
        RoleAssignmentSource::Manual,
    );

    $this
        ->withSession(
            administrationManagementSession($admin)
        )
        ->actingAs($admin)
        ->post(
            'http://my.vdb.test/verwaltung/benutzer/'.
            $target->id.
            '/rollen/'.
            $manual->id.
            '/beenden',
            [
                'end_comment' => 'Aufgabe beendet',
            ],
        )
        ->assertRedirect()
        ->assertSessionHas(
            'status_type',
            'success',
        );

    expect($manual->refresh()->ends_at)
        ->not->toBeNull()
        ->and(
            AuditEvent::query()
                ->where(
                    'event_key',
                    AuditEventCatalog::ROLE_MANUAL_ENDED,
                )
                ->count()
        )
        ->toBe(1);

    $automatic = giveAdministrationManagementRole(
        $target,
        RoleKey::Member,
        RoleAssignmentSource::Automatic,
    );

    $this
        ->withSession(
            administrationManagementSession($admin)
        )
        ->actingAs($admin)
        ->post(
            'http://my.vdb.test/verwaltung/benutzer/'.
            $target->id.
            '/rollen/'.
            $automatic->id.
            '/beenden',
            [
                'end_comment' => 'Soll geschützt bleiben',
            ],
        )
        ->assertRedirect()
        ->assertSessionHas(
            'status_type',
            'danger',
        );

    expect($automatic->refresh()->ends_at)
        ->toBeNull();
});

it('disables another active account and invalidates its sessions', function () {
    $admin = makeAdministrationManagementUser(
        'disable-admin@example.test'
    );
    giveAdministrationManagementRole(
        $admin,
        RoleKey::Administration,
    );

    $target = makeAdministrationManagementUser(
        'disable-target@example.test',
        UserStatus::Active,
        true,
        7,
    );
    $target->remember_token = 'remember-me';
    $target->save();

    $this
        ->withSession(
            administrationManagementSession($admin)
        )
        ->actingAs($admin)
        ->post(
            'http://my.vdb.test/verwaltung/benutzer/'.
            $target->id.
            '/status',
            [
                'status_action' => 'disable',
                'status_comment' => 'Zugang administrativ gesperrt',
            ],
        )
        ->assertRedirect()
        ->assertSessionHas(
            'status_type',
            'success',
        );

    $target->refresh();

    expect($target->status)
        ->toBe(UserStatus::Disabled)
        ->and($target->session_version)
        ->toBe(8)
        ->and($target->remember_token)
        ->toBeNull()
        ->and(
            AuditEvent::query()
                ->where(
                    'event_key',
                    AuditEventCatalog::ACCOUNT_DISABLED,
                )
                ->count()
        )
        ->toBe(1)
        ->and(
            AuditEvent::query()
                ->where(
                    'event_key',
                    AuditEventCatalog::AUTH_SESSIONS_INVALIDATED,
                )
                ->count()
        )
        ->toBe(1);
});

it('does not allow an administrator to disable their own account', function () {
    $admin = makeAdministrationManagementUser(
        'self-disable-admin@example.test'
    );
    giveAdministrationManagementRole(
        $admin,
        RoleKey::Administration,
    );

    $this
        ->withSession(
            administrationManagementSession($admin)
        )
        ->actingAs($admin)
        ->post(
            'http://my.vdb.test/verwaltung/benutzer/'.
            $admin->id.
            '/status',
            [
                'status_action' => 'disable',
                'status_comment' => 'Soll verhindert werden',
            ],
        )
        ->assertRedirect()
        ->assertSessionHas(
            'status_type',
            'danger',
        );

    expect($admin->refresh()->status)
        ->toBe(UserStatus::Active);
});

it('reactivates only disabled accounts with verified email addresses', function () {
    $admin = makeAdministrationManagementUser(
        'reactivate-admin@example.test'
    );
    giveAdministrationManagementRole(
        $admin,
        RoleKey::Administration,
    );

    $verified = makeAdministrationManagementUser(
        'reactivate-verified@example.test',
        UserStatus::Disabled,
        true,
        4,
    );

    $this
        ->withSession(
            administrationManagementSession($admin)
        )
        ->actingAs($admin)
        ->post(
            'http://my.vdb.test/verwaltung/benutzer/'.
            $verified->id.
            '/status',
            [
                'status_action' => 'reactivate',
                'status_comment' => 'Sperrgrund ist entfallen',
            ],
        )
        ->assertRedirect()
        ->assertSessionHas(
            'status_type',
            'success',
        );

    expect($verified->refresh()->status)
        ->toBe(UserStatus::Active)
        ->and(
            AuditEvent::query()
                ->where(
                    'event_key',
                    AuditEventCatalog::ACCOUNT_REACTIVATED,
                )
                ->count()
        )
        ->toBe(1);

    $unverified = makeAdministrationManagementUser(
        'reactivate-unverified@example.test',
        UserStatus::Disabled,
        false,
    );

    $this
        ->withSession(
            administrationManagementSession($admin)
        )
        ->actingAs($admin)
        ->post(
            'http://my.vdb.test/verwaltung/benutzer/'.
            $unverified->id.
            '/status',
            [
                'status_action' => 'reactivate',
                'status_comment' => 'Soll abgelehnt werden',
            ],
        )
        ->assertRedirect()
        ->assertSessionHas(
            'status_type',
            'danger',
        );

    expect($unverified->refresh()->status)
        ->toBe(UserStatus::Disabled);
});
