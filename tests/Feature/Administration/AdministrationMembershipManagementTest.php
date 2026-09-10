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
use App\Modules\Membership\Models\Membership;

function makeMembershipManagementActor(RoleKey $roleKey, string $email): User
{
    $actor = User::query()->create([
        'email' => $email,
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 1,
        'email_verified_at' => now(),
    ]);

    $role = Role::query()->firstOrCreate(
        ['key' => $roleKey->value],
        ['name' => $roleKey === RoleKey::Administration ? 'Administration' : 'Verwaltung', 'is_system' => true],
    );

    RoleAssignment::query()->create([
        'user_id' => $actor->id,
        'role_id' => $role->id,
        'source' => RoleAssignmentSource::Console,
        'starts_at' => now()->subMinute(),
    ]);

    Role::query()->firstOrCreate(
        ['key' => RoleKey::Member->value],
        ['name' => 'Mitglied', 'is_system' => true],
    );

    return $actor->refresh();
}

function membershipManagementSession(): array
{
    return [
        'identity.session_version' => 1,
        'identity.account_validated_at' => now()->timestamp,
    ];
}

function makeMembershipPerson(string $email): Person
{
    return Person::query()->create([
        'first_name' => 'Erika',
        'last_name' => 'Muster',
        'birth_date' => '1990-04-12',
        'email' => $email,
        'country_code' => 'DE',
    ]);
}

it('allows administration staff to read membership data but blocks write routes', function () {
    $staff = makeMembershipManagementActor(RoleKey::AdministrationStaff, 'membership-staff@example.test');
    $person = makeMembershipPerson('membership-person-staff@example.test');
    $membership = Membership::query()->create(['person_id' => $person->id, 'starts_on' => now()->subMonth()->toDateString()]);

    $this->withSession(membershipManagementSession())->actingAs($staff)->get('http://my.vdb.test/verwaltung/mitgliedschaften')->assertOk()->assertSee('Erika Muster');
    $this->withSession(membershipManagementSession())->actingAs($staff)->get('http://my.vdb.test/verwaltung/mitgliedschaften/'.$membership->id)->assertOk()->assertDontSee('Bearbeiten')->assertDontSee('Mitgliedschaft beenden');
    $this->withSession(membershipManagementSession())->actingAs($staff)->get('http://my.vdb.test/verwaltung/personen/'.$person->id.'/mitgliedschaften/anlegen')->assertForbidden();
    $this->withSession(membershipManagementSession())->actingAs($staff)->post('http://my.vdb.test/verwaltung/personen/'.$person->id.'/mitgliedschaften', ['starts_on' => now()->toDateString(), 'ends_on' => null])->assertForbidden();
    $this->withSession(membershipManagementSession())->actingAs($staff)->get('http://my.vdb.test/verwaltung/mitgliedschaften/'.$membership->id.'/bearbeiten')->assertForbidden();
    $this->withSession(membershipManagementSession())->actingAs($staff)->put('http://my.vdb.test/verwaltung/mitgliedschaften/'.$membership->id, ['starts_on' => now()->subMonth()->toDateString(), 'ends_on' => now()->addMonth()->toDateString()])->assertForbidden();
});

it('blocks membership administration for users without administration access', function () {
    $user = User::query()->create(['email' => 'membership-no-access@example.test', 'password' => 'Sicher123!', 'status' => UserStatus::Active, 'session_version' => 1, 'email_verified_at' => now()]);
    $this->withSession(membershipManagementSession())->actingAs($user)->get('http://my.vdb.test/verwaltung/mitgliedschaften')->assertForbidden();
});

it('creates a membership, audit event and synchronized member role', function () {
    $admin = makeMembershipManagementActor(RoleKey::Administration, 'membership-admin-create@example.test');
    $person = makeMembershipPerson('membership-create@example.test');
    $user = User::query()->create(['person_id' => $person->id, 'email' => $person->email, 'password' => 'Sicher123!', 'status' => UserStatus::Active, 'session_version' => 1, 'email_verified_at' => now()]);
    $startsOn = now()->subDays(10)->toDateString();

    $response = $this->withSession(membershipManagementSession())->actingAs($admin)->post('http://my.vdb.test/verwaltung/personen/'.$person->id.'/mitgliedschaften', ['starts_on' => $startsOn, 'ends_on' => null]);
    $membership = Membership::query()->where('person_id', $person->id)->firstOrFail();
    $response->assertRedirect(route('administration.memberships.show', $membership));

    $memberRole = Role::query()->where('key', RoleKey::Member->value)->firstOrFail();
    $assignment = RoleAssignment::query()->where('user_id', $user->id)->where('role_id', $memberRole->id)->where('source_type', 'membership')->where('source_id', $membership->id)->firstOrFail();
    expect($assignment->source)->toBe(RoleAssignmentSource::Automatic)
        ->and($assignment->starts_at->toDateString())->toBe($startsOn)
        ->and($assignment->ends_at)->toBeNull();

    $audit = AuditEvent::query()->where('event_key', AuditEventCatalog::MEMBERSHIP_CREATED)->where('subject_id', $membership->id)->firstOrFail();
    expect($audit->actor_user_id)->toBe($admin->id)->and($audit->new_values['person_id'] ?? null)->toBe($person->id);
});

it('creates memberships for persons without portal accounts without role assignments', function () {
    $admin = makeMembershipManagementActor(RoleKey::Administration, 'membership-admin-no-user@example.test');
    $person = makeMembershipPerson('membership-no-user@example.test');

    $this->withSession(membershipManagementSession())->actingAs($admin)->post('http://my.vdb.test/verwaltung/personen/'.$person->id.'/mitgliedschaften', ['starts_on' => now()->toDateString(), 'ends_on' => null])->assertSessionHasNoErrors();

    expect(Membership::query()->where('person_id', $person->id)->exists())->toBeTrue()
        ->and(RoleAssignment::query()->where('source_type', 'membership')->exists())->toBeFalse();
});

it('rejects overlapping membership periods for the same person', function () {
    $admin = makeMembershipManagementActor(RoleKey::Administration, 'membership-admin-overlap@example.test');
    $person = makeMembershipPerson('membership-overlap@example.test');
    Membership::query()->create(['person_id' => $person->id, 'starts_on' => now()->subMonth()->toDateString(), 'ends_on' => now()->addMonth()->toDateString()]);

    $this->withSession(membershipManagementSession())->actingAs($admin)->post('http://my.vdb.test/verwaltung/personen/'.$person->id.'/mitgliedschaften', ['starts_on' => now()->toDateString(), 'ends_on' => now()->addMonths(2)->toDateString()])->assertSessionHasErrors('starts_on');
    expect(Membership::query()->where('person_id', $person->id)->count())->toBe(1);
});

it('updates membership dates and keeps the automatic member role synchronized', function () {
    $admin = makeMembershipManagementActor(RoleKey::Administration, 'membership-admin-update@example.test');
    $person = makeMembershipPerson('membership-update@example.test');
    $user = User::query()->create(['person_id' => $person->id, 'email' => $person->email, 'password' => 'Sicher123!', 'status' => UserStatus::Active, 'session_version' => 1, 'email_verified_at' => now()]);
    $startsOn = now()->subMonths(2)->toDateString();
    $membership = Membership::query()->create(['person_id' => $person->id, 'starts_on' => $startsOn]);
    app(\App\Modules\Membership\Actions\SynchronizeMembershipRoleAction::class)->execute($membership);
    $newStart = now()->subMonth()->toDateString();
    $newEnd = now()->addMonth()->toDateString();

    $this->withSession(membershipManagementSession())->actingAs($admin)->put('http://my.vdb.test/verwaltung/mitgliedschaften/'.$membership->id, ['starts_on' => $newStart, 'ends_on' => $newEnd])->assertRedirect(route('administration.memberships.show', $membership));

    $membership->refresh();
    expect($membership->starts_on->toDateString())->toBe($newStart)->and($membership->ends_on?->toDateString())->toBe($newEnd);
    $assignment = RoleAssignment::query()->where('user_id', $user->id)->where('source_type', 'membership')->where('source_id', $membership->id)->firstOrFail();
    expect($assignment->starts_at->toDateString())->toBe($newStart)->and($assignment->ends_at?->toDateString())->toBe($newEnd);
    expect(AuditEvent::query()->where('event_key', AuditEventCatalog::MEMBERSHIP_UPDATED)->where('subject_id', $membership->id)->exists())->toBeTrue();
});

it('ends an open membership with a required reason and audits it', function () {
    $admin = makeMembershipManagementActor(RoleKey::Administration, 'membership-admin-end@example.test');
    $person = makeMembershipPerson('membership-end@example.test');
    $user = User::query()->create(['person_id' => $person->id, 'email' => $person->email, 'password' => 'Sicher123!', 'status' => UserStatus::Active, 'session_version' => 1, 'email_verified_at' => now()]);
    $membership = Membership::query()->create(['person_id' => $person->id, 'starts_on' => now()->subMonth()->toDateString()]);
    app(\App\Modules\Membership\Actions\SynchronizeMembershipRoleAction::class)->execute($membership);

    $this->withSession(membershipManagementSession())->actingAs($admin)->post('http://my.vdb.test/verwaltung/mitgliedschaften/'.$membership->id.'/beenden', ['ends_on' => now()->toDateString(), 'reason' => 'Austritt auf eigenen Wunsch'])->assertRedirect(route('administration.memberships.show', $membership));

    $membership->refresh();
    expect($membership->ends_on?->toDateString())->toBe(now()->toDateString());
    $assignment = RoleAssignment::query()->where('user_id', $user->id)->where('source_type', 'membership')->where('source_id', $membership->id)->firstOrFail();
    expect($assignment->ends_at?->toDateString())->toBe(now()->toDateString());
    $audit = AuditEvent::query()->where('event_key', AuditEventCatalog::MEMBERSHIP_ENDED)->where('subject_id', $membership->id)->firstOrFail();
    expect($audit->comment)->toBe('Austritt auf eigenen Wunsch');
});

it('requires a reason when ending a membership', function () {
    $admin = makeMembershipManagementActor(RoleKey::Administration, 'membership-admin-end-reason@example.test');
    $person = makeMembershipPerson('membership-end-reason@example.test');
    $membership = Membership::query()->create(['person_id' => $person->id, 'starts_on' => now()->subMonth()->toDateString()]);

    $this->withSession(membershipManagementSession())->actingAs($admin)->post('http://my.vdb.test/verwaltung/mitgliedschaften/'.$membership->id.'/beenden', ['ends_on' => now()->toDateString(), 'reason' => ''])->assertSessionHasErrors('reason');
    expect($membership->refresh()->ends_on)->toBeNull();
});

it('does not reopen ended memberships and supports resumption as a new period', function () {
    $admin = makeMembershipManagementActor(RoleKey::Administration, 'membership-admin-resume@example.test');
    $person = makeMembershipPerson('membership-resume@example.test');
    $oldStart = now()->subYear()->toDateString();
    $oldEnd = now()->subDay()->toDateString();
    $membership = Membership::query()->create(['person_id' => $person->id, 'starts_on' => $oldStart, 'ends_on' => $oldEnd]);

    $this->withSession(membershipManagementSession())->actingAs($admin)->put('http://my.vdb.test/verwaltung/mitgliedschaften/'.$membership->id, ['starts_on' => $oldStart, 'ends_on' => null])->assertSessionHasErrors('ends_on');
    $this->withSession(membershipManagementSession())->actingAs($admin)->post('http://my.vdb.test/verwaltung/personen/'.$person->id.'/mitgliedschaften', ['starts_on' => now()->toDateString(), 'ends_on' => null])->assertSessionHasNoErrors();

    expect(Membership::query()->where('person_id', $person->id)->count())->toBe(2)
        ->and($membership->refresh()->ends_on?->toDateString())->toBe($oldEnd);
});

it('filters memberships by person and derived status', function () {
    $staff = makeMembershipManagementActor(RoleKey::AdministrationStaff, 'membership-filter@example.test');
    $activePerson = makeMembershipPerson('active-filter@example.test');
    $endedPerson = makeMembershipPerson('ended-filter@example.test');
    Membership::query()->create(['person_id' => $activePerson->id, 'starts_on' => now()->subMonth()->toDateString()]);
    Membership::query()->create(['person_id' => $endedPerson->id, 'starts_on' => now()->subYear()->toDateString(), 'ends_on' => now()->subMonth()->toDateString()]);

    $this->withSession(membershipManagementSession())->actingAs($staff)->get('http://my.vdb.test/verwaltung/mitgliedschaften?q=active-filter&status=active')->assertOk()->assertSee('active-filter@example.test')->assertDontSee('ended-filter@example.test');
    $this->withSession(membershipManagementSession())->actingAs($staff)->get('http://my.vdb.test/verwaltung/mitgliedschaften?status=ended')->assertOk()->assertSee('ended-filter@example.test')->assertDontSee('active-filter@example.test');
});

it('shows membership history on person details and paginates the directory', function () {
    $staff = makeMembershipManagementActor(RoleKey::AdministrationStaff, 'membership-pagination@example.test');
    $firstPerson = makeMembershipPerson('history@example.test');
    Membership::query()->create(['person_id' => $firstPerson->id, 'starts_on' => now()->subYear()->toDateString(), 'ends_on' => now()->subMonths(6)->toDateString()]);
    Membership::query()->create(['person_id' => $firstPerson->id, 'starts_on' => now()->subMonth()->toDateString()]);

    foreach (range(1, 24) as $index) {
        $person = makeMembershipPerson(sprintf('membership-page-%02d@example.test', $index));
        Membership::query()->create(['person_id' => $person->id, 'starts_on' => now()->subDays($index)->toDateString()]);
    }

    $this->withSession(membershipManagementSession())->actingAs($staff)->get('http://my.vdb.test/verwaltung/personen/'.$firstPerson->id)->assertOk()->assertSee('Mitgliedschaftsverlauf')->assertSee('2')->assertDontSee('Mitgliedschaft anlegen');
    $this->withSession(membershipManagementSession())->actingAs($staff)->get('http://my.vdb.test/verwaltung/mitgliedschaften')->assertOk()->assertSee('26 Mitgliedschaften')->assertSee('Seite 1 von 2')->assertSee('Weiter');
});
