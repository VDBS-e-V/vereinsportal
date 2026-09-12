<?php

use App\Modules\Identity\Enums\RoleAssignmentSource;
use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\RoleAssignment;
use App\Modules\Identity\Models\User;
use App\Modules\Membership\Models\Membership;

function makeAccountAreaUser(string $email): User
{
    $person = Person::query()->create([
        'first_name' => 'Klara',
        'last_name' => 'Konto',
        'birth_date' => '1990-01-02',
        'email' => $email,
        'country_code' => 'DE',
    ]);

    $user = User::query()->create([
        'person_id' => $person->id,
        'email' => $email,
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 1,
    ]);
    $user->email_verified_at = now();
    $user->save();

    return $user->refresh();
}

function giveAccountAreaRole(User $user, RoleKey $roleKey): void
{
    $role = Role::query()->firstOrCreate(
        ['key' => $roleKey->value],
        [
            'name' => $roleKey->name,
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

function accountAreaSession(User $user): array
{
    return [
        'identity.session_version' => $user->session_version,
        'identity.account_validated_at' => now()->timestamp,
    ];
}

it('shows the account structure and links every implemented setting correctly', function () {
    $user = makeAccountAreaUser('account-structure@example.test');

    $this
        ->withSession(accountAreaSession($user))
        ->actingAs($user)
        ->get('http://my.vdb.test/konto')
        ->assertOk()
        ->assertSee('Mein Profil')
        ->assertSee('Kontoeinstellungen')
        ->assertSee('Meine Tickets')
        ->assertDontSee('Mitgliedschaft')
        ->assertDontSee('Teamendeneinstellungen');

    $response = $this
        ->withSession(accountAreaSession($user))
        ->actingAs($user)
        ->get('http://my.vdb.test/konto/einstellungen')
        ->assertOk()
        ->assertSee('Kontodaten')
        ->assertSee('2FA')
        ->assertSee('E-Mail-Änderung')
        ->assertSee('Passwort ändern')
        ->assertSee('Konto löschen');

    foreach ([
        'my.profile',
        'my.security',
        'my.email-change',
        'my.password.change',
        'my.account-deletion',
    ] as $routeName) {
        $response->assertSee(route($routeName), false);
    }
});

it('shows membership only to active members and protects the membership page', function () {
    $member = makeAccountAreaUser('account-member@example.test');
    giveAccountAreaRole($member, RoleKey::Member);

    Membership::query()->create([
        'person_id' => $member->person_id,
        'starts_on' => '2026-01-15',
    ]);

    $this
        ->withSession(accountAreaSession($member))
        ->actingAs($member)
        ->get('http://my.vdb.test/konto')
        ->assertOk()
        ->assertSee('Mitgliedschaft')
        ->assertSee(route('my.membership'), false);

    $this
        ->withSession(accountAreaSession($member))
        ->actingAs($member)
        ->get('http://my.vdb.test/konto/mitgliedschaft')
        ->assertOk()
        ->assertSee('Mitgliedschaftsverlauf')
        ->assertSee('15.01.2026')
        ->assertSee('Aktiv');

    $nonMember = makeAccountAreaUser('account-non-member@example.test');

    $this
        ->withSession(accountAreaSession($nonMember))
        ->actingAs($nonMember)
        ->get('http://my.vdb.test/konto/mitgliedschaft')
        ->assertForbidden();
});

it('shows team settings only to active team members', function () {
    $teamMember = makeAccountAreaUser('account-team@example.test');
    giveAccountAreaRole($teamMember, RoleKey::Team);

    $this
        ->withSession(accountAreaSession($teamMember))
        ->actingAs($teamMember)
        ->get('http://my.vdb.test/konto')
        ->assertOk()
        ->assertSee('Teamendeneinstellungen')
        ->assertDontSee('Mitgliedschaft');

    $normalUser = makeAccountAreaUser('account-normal@example.test');

    $this
        ->withSession(accountAreaSession($normalUser))
        ->actingAs($normalUser)
        ->get('http://my.vdb.test/konto')
        ->assertOk()
        ->assertDontSee('Teamendeneinstellungen');
});
