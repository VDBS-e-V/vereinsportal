<?php

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Enums\RoleAssignmentSource;
use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\RoleAssignment;
use App\Modules\Identity\Models\User;

function makePersonManagementActor(
    RoleKey $roleKey,
    string $email,
): User {
    $actor = User::query()->create([
        'email' => $email,
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 1,
    ]);

    $actor->email_verified_at = now();
    $actor->save();

    $role = Role::query()->firstOrCreate(
        ['key' => $roleKey->value],
        [
            'name' => match ($roleKey) {
                RoleKey::Administration => 'Administration',
                RoleKey::AdministrationStaff => 'Verwaltung',
                default => $roleKey->value,
            },
            'is_system' => true,
        ],
    );

    RoleAssignment::query()->create([
        'user_id' => $actor->id,
        'role_id' => $role->id,
        'source' => RoleAssignmentSource::Console,
        'starts_at' => now()->subMinute(),
    ]);

    return $actor->refresh();
}

function personManagementSession(): array
{
    return [
        'identity.session_version' => 1,
        'identity.account_validated_at' => now()->timestamp,
    ];
}

function personManagementPayload(array $overrides = []): array
{
    return array_merge([
        'title' => null,
        'first_name' => 'Erika',
        'name_addition' => null,
        'last_name' => 'Muster',
        'birth_date' => '1990-04-12',
        'email' => 'erika.muster@example.test',
        'phone' => '01234 567890',
        'street' => 'Musterstraße',
        'house_number' => '12',
        'postal_code' => '12345',
        'city' => 'Musterstadt',
        'country_code' => 'DE',
    ], $overrides);
}

it('allows administration staff to search and view persons read only', function () {
    $staff = makePersonManagementActor(RoleKey::AdministrationStaff, 'person-staff@example.test');
    $visible = Person::query()->create(personManagementPayload(['first_name' => 'Erika', 'last_name' => 'Muster', 'email' => 'visible.person@example.test']));
    Person::query()->create(personManagementPayload(['first_name' => 'Max', 'last_name' => 'Beispiel', 'email' => 'hidden.person@example.test']));

    $this->withSession(personManagementSession())->actingAs($staff)->get('http://my.vdb.test/verwaltung/personen?q=Muster')->assertOk()->assertSee('Erika Muster')->assertSee('visible.person@example.test')->assertDontSee('Max Beispiel')->assertDontSee('Person anlegen');
    $this->withSession(personManagementSession())->actingAs($staff)->get('http://my.vdb.test/verwaltung/personen/'.$visible->id)->assertOk()->assertSee('Erika Muster')->assertSee('visible.person@example.test')->assertDontSee('Bearbeiten');
});

it('blocks person administration for users without an administration role', function () {
    $user = User::query()->create(['email' => 'person-no-access@example.test', 'password' => 'Sicher123!', 'status' => UserStatus::Active, 'session_version' => 1, 'email_verified_at' => now()]);
    $this->withSession(personManagementSession())->actingAs($user)->get('http://my.vdb.test/verwaltung/personen')->assertForbidden();
});

it('keeps administration staff read only on person write routes', function () {
    $staff = makePersonManagementActor(RoleKey::AdministrationStaff, 'person-readonly@example.test');
    $person = Person::query()->create(personManagementPayload(['email' => 'readonly.person@example.test']));
    $this->withSession(personManagementSession())->actingAs($staff)->get('http://my.vdb.test/verwaltung/personen/anlegen')->assertForbidden();
    $this->withSession(personManagementSession())->actingAs($staff)->post('http://my.vdb.test/verwaltung/personen', personManagementPayload(['email' => 'blocked.create@example.test']))->assertForbidden();
    $this->withSession(personManagementSession())->actingAs($staff)->get('http://my.vdb.test/verwaltung/personen/'.$person->id.'/bearbeiten')->assertForbidden();
    $this->withSession(personManagementSession())->actingAs($staff)->put('http://my.vdb.test/verwaltung/personen/'.$person->id, personManagementPayload(['email' => $person->email, 'city' => 'Nicht erlaubt']))->assertForbidden();
});

it('creates a normalized person and writes an audit event', function () {
    $admin = makePersonManagementActor(RoleKey::Administration, 'person-admin-create@example.test');
    $response = $this->withSession(personManagementSession())->actingAs($admin)->post('http://my.vdb.test/verwaltung/personen', personManagementPayload(['first_name' => '  Erika  ', 'email' => '  ERIKA.NEW@EXAMPLE.TEST ', 'country_code' => 'de']));
    $person = Person::query()->where('email', 'erika.new@example.test')->firstOrFail();
    $response->assertRedirect(route('administration.persons.show', $person));
    expect($person->first_name)->toBe('Erika')->and($person->country_code)->toBe('DE');
    $audit = AuditEvent::query()->where('event_key', AuditEventCatalog::PERSON_CREATED)->firstOrFail();
    expect($audit->actor_user_id)->toBe($admin->id)->and($audit->subject_type)->toBe('person')->and($audit->subject_id)->toBe($person->id)->and($audit->new_values['email'] ?? null)->toBe('erika.new@example.test');
});

it('requires conscious confirmation when a possible duplicate is found', function () {
    $admin = makePersonManagementActor(RoleKey::Administration, 'person-admin-duplicate@example.test');
    $existing = Person::query()->create(personManagementPayload(['first_name' => 'Lena', 'last_name' => 'Beispiel', 'birth_date' => '1988-03-05', 'email' => 'lena.existing@example.test']));
    $payload = personManagementPayload(['first_name' => 'Lena', 'last_name' => 'Beispiel', 'birth_date' => '1988-03-05', 'email' => 'lena.new@example.test']);
    $this->withSession(personManagementSession())->actingAs($admin)->post('http://my.vdb.test/verwaltung/personen', $payload)->assertRedirect(route('administration.persons.create'))->assertSessionHas('possible_person_match_ids', [$existing->id]);
    expect(Person::query()->count())->toBe(1);
    $this->withSession(personManagementSession())->actingAs($admin)->post('http://my.vdb.test/verwaltung/personen', [...$payload, 'confirm_possible_duplicate' => '1'])->assertSessionHasNoErrors();
    expect(Person::query()->count())->toBe(2)->and(Person::query()->where('email', 'lena.new@example.test')->exists())->toBeTrue();
});

it('rejects person emails already used by a person or user', function () {
    $admin = makePersonManagementActor(RoleKey::Administration, 'person-admin-unique@example.test');
    Person::query()->create(personManagementPayload(['email' => 'existing.person@example.test']));
    $this->withSession(personManagementSession())->actingAs($admin)->post('http://my.vdb.test/verwaltung/personen', personManagementPayload(['first_name' => 'Andere', 'last_name' => 'Person', 'email' => 'EXISTING.PERSON@EXAMPLE.TEST', 'confirm_possible_duplicate' => '1']))->assertSessionHasErrors('email');
    User::query()->create(['email' => 'existing.user@example.test', 'password' => 'Sicher123!', 'status' => UserStatus::Active, 'session_version' => 1]);
    $this->withSession(personManagementSession())->actingAs($admin)->post('http://my.vdb.test/verwaltung/personen', personManagementPayload(['first_name' => 'Noch', 'last_name' => 'Jemand', 'email' => 'existing.user@example.test', 'confirm_possible_duplicate' => '1']))->assertSessionHasErrors('email');
});

it('updates an unlinked person and audits changed values', function () {
    $admin = makePersonManagementActor(RoleKey::Administration, 'person-admin-update@example.test');
    $person = Person::query()->create(personManagementPayload(['email' => 'before@example.test', 'city' => 'Altstadt']));
    $this->withSession(personManagementSession())->actingAs($admin)->put('http://my.vdb.test/verwaltung/personen/'.$person->id, personManagementPayload(['email' => ' AFTER@EXAMPLE.TEST ', 'city' => 'Neustadt']))->assertRedirect(route('administration.persons.show', $person));
    $person->refresh();
    expect($person->email)->toBe('after@example.test')->and($person->city)->toBe('Neustadt');
    $audit = AuditEvent::query()->where('event_key', AuditEventCatalog::PERSON_UPDATED)->where('subject_id', $person->id)->firstOrFail();
    expect($audit->actor_user_id)->toBe($admin->id)->and($audit->old_values['city'] ?? null)->toBe('Altstadt')->and($audit->new_values['city'] ?? null)->toBe('Neustadt')->and($audit->old_values['email'] ?? null)->toBe('before@example.test')->and($audit->new_values['email'] ?? null)->toBe('after@example.test');
});

it('protects the email of a person linked to a user account', function () {
    $admin = makePersonManagementActor(RoleKey::Administration, 'person-admin-linked@example.test');
    $person = Person::query()->create(personManagementPayload(['email' => 'linked.person@example.test']));
    User::query()->create(['person_id' => $person->id, 'email' => $person->email, 'password' => 'Sicher123!', 'status' => UserStatus::Active, 'session_version' => 1, 'email_verified_at' => now()]);
    $this->withSession(personManagementSession())->actingAs($admin)->put('http://my.vdb.test/verwaltung/personen/'.$person->id, personManagementPayload(['email' => 'changed@example.test']))->assertSessionHasErrors('email');
    expect($person->refresh()->email)->toBe('linked.person@example.test');
    $this->withSession(personManagementSession())->actingAs($admin)->put('http://my.vdb.test/verwaltung/personen/'.$person->id, personManagementPayload(['email' => 'linked.person@example.test', 'phone' => '09876 543210']))->assertRedirect(route('administration.persons.show', $person));
    expect($person->refresh()->phone)->toBe('09876 543210');
});

it('paginates the person directory', function () {
    $staff = makePersonManagementActor(RoleKey::AdministrationStaff, 'person-pagination@example.test');
    foreach (range(1, 26) as $index) {
        Person::query()->create(personManagementPayload(['first_name' => 'Person', 'last_name' => sprintf('Test%02d', $index), 'email' => sprintf('person-%02d@example.test', $index)]));
    }
    $this->withSession(personManagementSession())->actingAs($staff)->get('http://my.vdb.test/verwaltung/personen')->assertOk()->assertSee('26 Personen')->assertSee('Seite 1 von 2')->assertSee('Weiter');
});
