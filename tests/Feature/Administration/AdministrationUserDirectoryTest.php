<?php

use App\Modules\Identity\Enums\RoleAssignmentSource;
use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\RoleAssignment;
use App\Modules\Identity\Models\User;

function makeAdministrationDirectoryAdmin(): User
{
    $admin = User::query()->create([
        'email' => 'directory-admin@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 1,
    ]);

    $admin->email_verified_at = now();
    $admin->save();

    $role = Role::query()->firstOrCreate(
        [
            'key' => RoleKey::AdministrationStaff->value,
        ],
        [
            'name' => 'Verwaltung',
            'is_system' => true,
        ],
    );

    RoleAssignment::query()->create([
        'user_id' => $admin->id,
        'role_id' => $role->id,
        'source' => RoleAssignmentSource::Console,
        'starts_at' => now()->subMinute(),
    ]);

    return $admin;
}

function makeAdministrationDirectoryPersonUser(
    string $firstName,
    string $lastName,
    string $email,
    UserStatus $status,
): User {
    $person = Person::query()->create([
        'first_name' => $firstName,
        'last_name' => $lastName,
        'birth_date' => '1990-04-12',
        'email' => $email,
        'country_code' => 'DE',
    ]);

    $user = User::query()->create([
        'person_id' => $person->id,
        'email' => $email,
        'password' => 'Sicher123!',
        'status' => $status,
        'session_version' => 1,
    ]);

    if ($status === UserStatus::Active) {
        $user->email_verified_at = now();
        $user->save();
    }

    return $user->refresh();
}

it('searches and filters the administration user directory', function () {
    $admin = makeAdministrationDirectoryAdmin();

    makeAdministrationDirectoryPersonUser(
        'Erika',
        'Muster',
        'erika.muster@example.test',
        UserStatus::Active,
    );

    makeAdministrationDirectoryPersonUser(
        'Max',
        'Beispiel',
        'max.beispiel@example.test',
        UserStatus::Disabled,
    );

    $this
        ->withSession([
            'identity.session_version' => 1,
            'identity.account_validated_at' => now()->timestamp,
        ])
        ->actingAs($admin)
        ->get(
            'http://my.vdb.test/verwaltung/benutzer'.
            '?q=Muster&status=active'
        )
        ->assertOk()
        ->assertSee('Benutzerverwaltung')
        ->assertSee('Erika Muster')
        ->assertSee('erika.muster@example.test')
        ->assertDontSee('Max Beispiel');
});

it('keeps the system managed member role out of the user detail page', function () {
    $admin = makeAdministrationDirectoryAdmin();

    $user = makeAdministrationDirectoryPersonUser(
        'Erika',
        'Muster',
        'detail.muster@example.test',
        UserStatus::Active,
    );

    $memberRole = Role::query()->firstOrCreate(
        [
            'key' => RoleKey::Member->value,
        ],
        [
            'name' => 'Vereinsmitglied',
            'is_system' => true,
        ],
    );

    RoleAssignment::query()->create([
        'user_id' => $user->id,
        'role_id' => $memberRole->id,
        'source' => RoleAssignmentSource::Automatic,
        'starts_at' => now()->subMonth(),
    ]);

    $this
        ->withSession([
            'identity.session_version' => 1,
            'identity.account_validated_at' => now()->timestamp,
        ])
        ->actingAs($admin)
        ->get('http://my.vdb.test/verwaltung/benutzer/'.$user->id)
        ->assertOk()
        ->assertSee('Erika Muster')
        ->assertSee('detail.muster@example.test')
        ->assertDontSee('Vereinsmitglied')
        ->assertSee('Aktiv');
});

it('shows the administration dashboard with real account metrics', function () {
    $admin = makeAdministrationDirectoryAdmin();

    makeAdministrationDirectoryPersonUser(
        'Anna',
        'Aktiv',
        'anna.active@example.test',
        UserStatus::Active,
    );

    makeAdministrationDirectoryPersonUser(
        'Peter',
        'Prüfung',
        'peter.pending@example.test',
        UserStatus::PendingVerification,
    );

    $this
        ->withSession([
            'identity.session_version' => 1,
            'identity.account_validated_at' => now()->timestamp,
        ])
        ->actingAs($admin)
        ->get('http://my.vdb.test/verwaltung')
        ->assertOk()
        ->assertSee('Konten gesamt')
        ->assertSee('Aktive Konten')
        ->assertSee('Bestätigung offen')
        ->assertSee('Benutzerverwaltung');
});
