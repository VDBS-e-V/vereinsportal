<?php

use App\Modules\Identity\Enums\RoleAssignmentSource;
use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\PortalInvitation;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\RoleAssignment;
use App\Modules\Identity\Models\User;
use App\Modules\Membership\Enums\MembershipConsentSource;
use App\Modules\Membership\Enums\MembershipDocumentType;
use App\Modules\Membership\Models\Membership;
use App\Modules\Membership\Models\MembershipConsent;
use App\Modules\Membership\Models\MembershipDocument;
use Illuminate\Support\Str;

function makeAdministrationDetailActor(): User
{
    $actor = User::query()->create([
        'email' => 'detail-rendering-admin@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 1,
    ]);
    $actor->email_verified_at = now();
    $actor->save();

    $role = Role::query()->firstOrCreate(
        ['key' => RoleKey::Administration->value],
        [
            'name' => 'Administration',
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

function administrationDetailSession(): array
{
    return [
        'identity.session_version' => 1,
        'identity.account_validated_at' => now()->timestamp,
    ];
}

it('renders person and membership details with linked beta records', function () {
    $actor = makeAdministrationDetailActor();

    $person = Person::query()->create([
        'first_name' => 'Mira',
        'last_name' => 'Mitglied',
        'birth_date' => '1992-05-16',
        'email' => 'mira.mitglied@example.test',
        'phone' => '030 1234567',
        'postal_code' => '10115',
        'city' => 'Berlin',
        'country_code' => 'DE',
    ]);

    $linkedUser = User::query()->create([
        'person_id' => $person->id,
        'email' => $person->email,
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 1,
    ]);
    $linkedUser->email_verified_at = now();
    $linkedUser->save();

    $membership = Membership::query()->create([
        'person_id' => $person->id,
        'starts_on' => now()->subYear()->toDateString(),
    ]);

    PortalInvitation::query()->create([
        'public_id' => (string) Str::uuid(),
        'person_id' => $person->id,
        'email' => $person->email,
        'token_hash' => hash('sha256', 'detail-rendering-token'),
        'token_version' => 1,
        'expires_at' => now()->addDay(),
        'sent_at' => now()->subHour(),
        'accepted_at' => now()->subMinutes(30),
        'created_by_user_id' => $actor->id,
    ]);

    MembershipDocument::query()->create([
        'membership_id' => $membership->id,
        'document_type' => MembershipDocumentType::ApplicationForm,
        'disk' => 'local',
        'path' => 'memberships/'.$membership->id.'/documents/beitritt.pdf',
        'original_name' => 'beitritt.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 4096,
        'sha256' => hash('sha256', 'detail-rendering-document'),
        'received_on' => now()->subYear()->toDateString(),
        'uploaded_by_user_id' => $actor->id,
    ]);

    MembershipConsent::query()->create([
        'membership_id' => $membership->id,
        'consent_key' => 'foto.veroeffentlichung',
        'label' => 'Fotoveröffentlichung',
        'version' => '2026-01',
        'source' => MembershipConsentSource::Paper,
        'granted_at' => now()->subMonths(6),
        'recorded_by_user_id' => $actor->id,
    ]);

    $this
        ->withSession(administrationDetailSession())
        ->actingAs($actor)
        ->get('http://my.vdb.test/verwaltung/personen/'.$person->id)
        ->assertOk()
        ->assertSee('Mira Mitglied')
        ->assertSee('Mitgliedschaft ab')
        ->assertSee('Benutzerkonto öffnen');

    $this
        ->withSession(administrationDetailSession())
        ->actingAs($actor)
        ->get('http://my.vdb.test/vorstand/mitgliedschaften/'.$membership->id)
        ->assertOk()
        ->assertSee('Mira Mitglied')
        ->assertSee('beitritt.pdf')
        ->assertSee('Fotoveröffentlichung');
});
