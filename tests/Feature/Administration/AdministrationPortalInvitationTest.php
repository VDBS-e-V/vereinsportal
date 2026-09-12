<?php

use App\Modules\Identity\Enums\RoleAssignmentSource;
use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\PortalInvitation;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\RoleAssignment;
use App\Modules\Identity\Models\User;

function portalInvitationAdministrationActor(
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

function portalInvitationAdministrationSession(): array
{
    return [
        'identity.session_version' => 1,
        'identity.account_validated_at' => now()->timestamp,
    ];
}

function portalInvitationAdministrationPerson(string $email): Person
{
    return Person::query()->create([
        'first_name' => 'Lena',
        'last_name' => 'Beispiel',
        'birth_date' => '1988-03-05',
        'email' => $email,
        'country_code' => 'DE',
    ]);
}

it('allows administration staff to create portal invitations', function () {
    $staff = portalInvitationAdministrationActor(
        RoleKey::AdministrationStaff,
        'invitation-staff@example.test',
    );
    $person = portalInvitationAdministrationPerson('invitation-staff-target@example.test');

    $this->withSession(portalInvitationAdministrationSession())
        ->actingAs($staff)
        ->post(route('administration.persons.portal-invitations.store', $person))
        ->assertRedirect(route('administration.persons.show', $person));

    expect(PortalInvitation::query()->where('person_id', $person->id)->count())->toBe(1);
});

it('keeps a fail closed invitation visible when the email template is not ready', function () {
    $admin = portalInvitationAdministrationActor(
        RoleKey::Administration,
        'invitation-admin@example.test',
    );
    $person = portalInvitationAdministrationPerson('invitation-target@example.test');

    $this->withSession(portalInvitationAdministrationSession())
        ->actingAs($admin)
        ->post(route('administration.persons.portal-invitations.store', $person))
        ->assertRedirect(route('administration.persons.show', $person))
        ->assertSessionHas('status_type', 'warning');

    $invitation = PortalInvitation::query()->where('person_id', $person->id)->firstOrFail();

    expect($invitation->sent_at)->toBeNull()
        ->and($invitation->accepted_at)->toBeNull()
        ->and($invitation->revoked_at)->toBeNull();

    $this->withSession(portalInvitationAdministrationSession())
        ->actingAs($admin)
        ->get(route('administration.persons.show', $person))
        ->assertOk()
        ->assertSee('Einladung offen')
        ->assertSee('noch nicht versendet')
        ->assertSee('Erneut senden')
        ->assertSee('Widerrufen')
        ->assertSee('Einladungshistorie');
});

it('shows invitation state and controls to administration staff', function () {
    $admin = portalInvitationAdministrationActor(
        RoleKey::Administration,
        'invitation-create-admin@example.test',
    );
    $staff = portalInvitationAdministrationActor(
        RoleKey::AdministrationStaff,
        'invitation-manage-staff@example.test',
    );
    $person = portalInvitationAdministrationPerson('invitation-visible@example.test');

    $this->withSession(portalInvitationAdministrationSession())
        ->actingAs($admin)
        ->post(route('administration.persons.portal-invitations.store', $person));

    $invitation = PortalInvitation::query()->where('person_id', $person->id)->firstOrFail();

    $this->withSession(portalInvitationAdministrationSession())
        ->actingAs($staff)
        ->get(route('administration.persons.show', $person))
        ->assertOk()
        ->assertSee('Einladung offen')
        ->assertSee('Erneut senden')
        ->assertSee('Widerrufen')
        ->assertDontSee('Portalzugang einladen');

    $this->withSession(portalInvitationAdministrationSession())
        ->actingAs($staff)
        ->post(route('administration.portal-invitations.revoke', $invitation))
        ->assertRedirect(route('administration.persons.show', $person))
        ->assertSessionHas('status_type', 'success');

    expect($invitation->refresh()->revoked_at)->not->toBeNull();
});

it('allows administration to revoke an open invitation', function () {
    $admin = portalInvitationAdministrationActor(
        RoleKey::Administration,
        'invitation-revoke-admin@example.test',
    );
    $person = portalInvitationAdministrationPerson('invitation-revoke@example.test');

    $this->withSession(portalInvitationAdministrationSession())
        ->actingAs($admin)
        ->post(route('administration.persons.portal-invitations.store', $person));

    $invitation = PortalInvitation::query()->where('person_id', $person->id)->firstOrFail();

    $this->withSession(portalInvitationAdministrationSession())
        ->actingAs($admin)
        ->post(route('administration.portal-invitations.revoke', $invitation))
        ->assertRedirect(route('administration.persons.show', $person))
        ->assertSessionHas('status_type', 'success');

    expect($invitation->refresh()->revoked_at)->not->toBeNull();
});
