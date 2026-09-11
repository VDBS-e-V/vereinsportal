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
use App\Modules\Membership\Enums\MembershipDocumentType;
use App\Modules\Membership\Models\Membership;
use App\Modules\Membership\Models\MembershipConsent;
use App\Modules\Membership\Models\MembershipDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function makeMembershipRecordsActor(
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
            'name' => $roleKey === RoleKey::Administration
                ? 'Administration'
                : 'Verwaltung',
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

function membershipRecordsSession(): array
{
    return [
        'identity.session_version' => 1,
        'identity.account_validated_at' => now()->timestamp,
    ];
}

function makeMembershipRecordsMembership(string $email): Membership
{
    $person = Person::query()->create([
        'first_name' => 'Mira',
        'last_name' => 'Mitglied',
        'birth_date' => '1992-05-16',
        'email' => $email,
        'country_code' => 'DE',
    ]);

    return Membership::query()->create([
        'person_id' => $person->id,
        'starts_on' => now()->subMonth()->toDateString(),
    ]);
}

it('stores membership documents only on the private disk and audits the upload', function () {
    Storage::fake('local');
    Storage::fake('public');

    $admin = makeMembershipRecordsActor(
        RoleKey::Administration,
        'membership-records-admin-upload@example.test',
    );
    $membership = makeMembershipRecordsMembership(
        'membership-records-upload@example.test',
    );

    $this
        ->withSession(membershipRecordsSession())
        ->actingAs($admin)
        ->post(
            'http://my.vdb.test/verwaltung/mitgliedschaften/'.$membership->id.'/dokumente',
            [
                'document_type' => MembershipDocumentType::ApplicationForm->value,
                'label' => 'Beitrittserklärung 2026',
                'received_on' => '2026-09-01',
                'document' => UploadedFile::fake()->create(
                    'beitritt.pdf',
                    120,
                    'application/pdf',
                ),
            ],
        )
        ->assertRedirect(route('administration.memberships.show', $membership))
        ->assertSessionHasNoErrors();

    $document = MembershipDocument::query()->sole();

    expect($document->membership_id)
        ->toBe($membership->id)
        ->and($document->document_type)
        ->toBe(MembershipDocumentType::ApplicationForm)
        ->and($document->disk)
        ->toBe('local')
        ->and($document->sha256)
        ->toHaveLength(64)
        ->and($document->supersedes_document_id)
        ->toBeNull();

    Storage::disk('local')->assertExists($document->path);
    Storage::disk('public')->assertMissing($document->path);

    expect(
        AuditEvent::query()
            ->where('event_key', AuditEventCatalog::MEMBERSHIP_DOCUMENT_UPLOADED)
            ->where('subject_id', $document->id)
            ->exists(),
    )->toBeTrue();
});

it('rejects unsupported and oversized membership documents', function () {
    Storage::fake('local');

    $admin = makeMembershipRecordsActor(
        RoleKey::Administration,
        'membership-records-admin-validation@example.test',
    );
    $membership = makeMembershipRecordsMembership(
        'membership-records-validation@example.test',
    );
    $url = 'http://my.vdb.test/verwaltung/mitgliedschaften/'.$membership->id.'/dokumente';

    $this
        ->withSession(membershipRecordsSession())
        ->actingAs($admin)
        ->post($url, [
            'document_type' => MembershipDocumentType::Other->value,
            'document' => UploadedFile::fake()->create(
                'script.txt',
                10,
                'text/plain',
            ),
        ])
        ->assertSessionHasErrors('document');

    $this
        ->withSession(membershipRecordsSession())
        ->actingAs($admin)
        ->post($url, [
            'document_type' => MembershipDocumentType::ApplicationForm->value,
            'document' => UploadedFile::fake()->create(
                'zu-gross.pdf',
                10241,
                'application/pdf',
            ),
        ])
        ->assertSessionHasErrors('document');

    expect(MembershipDocument::query()->count())->toBe(0);
});

it('keeps the previous document when a new version replaces it', function () {
    Storage::fake('local');

    $admin = makeMembershipRecordsActor(
        RoleKey::Administration,
        'membership-records-admin-replace@example.test',
    );
    $membership = makeMembershipRecordsMembership(
        'membership-records-replace@example.test',
    );
    $baseUrl = 'http://my.vdb.test/verwaltung/mitgliedschaften/'.$membership->id;

    $this
        ->withSession(membershipRecordsSession())
        ->actingAs($admin)
        ->post($baseUrl.'/dokumente', [
            'document_type' => MembershipDocumentType::ApplicationForm->value,
            'document' => UploadedFile::fake()->create(
                'beitritt-alt.pdf',
                80,
                'application/pdf',
            ),
        ])
        ->assertSessionHasNoErrors();

    $oldDocument = MembershipDocument::query()->sole();
    $oldPath = $oldDocument->path;

    $this
        ->withSession(membershipRecordsSession())
        ->actingAs($admin)
        ->post(
            $baseUrl.'/dokumente/'.$oldDocument->id.'/ersetzen',
            [
                'received_on' => '2026-09-10',
                'document' => UploadedFile::fake()->create(
                    'beitritt-neu.pdf',
                    90,
                    'application/pdf',
                ),
            ],
        )
        ->assertSessionHasNoErrors();

    $newDocument = MembershipDocument::query()
        ->whereKeyNot($oldDocument->id)
        ->sole();

    expect(MembershipDocument::query()->count())
        ->toBe(2)
        ->and($newDocument->supersedes_document_id)
        ->toBe($oldDocument->id)
        ->and($newDocument->document_type)
        ->toBe($oldDocument->document_type);

    Storage::disk('local')->assertExists($oldPath);
    Storage::disk('local')->assertExists($newDocument->path);

    expect(
        AuditEvent::query()
            ->where('event_key', AuditEventCatalog::MEMBERSHIP_DOCUMENT_REPLACED)
            ->where('subject_id', $newDocument->id)
            ->exists(),
    )->toBeTrue();
});

it('allows read-only administration staff to download but not change documents', function () {
    Storage::fake('local');

    $staff = makeMembershipRecordsActor(
        RoleKey::AdministrationStaff,
        'membership-records-staff@example.test',
    );
    $membership = makeMembershipRecordsMembership(
        'membership-records-staff-person@example.test',
    );
    Storage::disk('local')->put(
        'memberships/'.$membership->id.'/documents/test.pdf',
        'private document',
    );
    $document = MembershipDocument::query()->create([
        'membership_id' => $membership->id,
        'document_type' => MembershipDocumentType::ApplicationForm,
        'disk' => 'local',
        'path' => 'memberships/'.$membership->id.'/documents/test.pdf',
        'original_name' => 'beitritt.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 16,
        'sha256' => hash('sha256', 'private document'),
    ]);

    $baseUrl = 'http://my.vdb.test/verwaltung/mitgliedschaften/'.$membership->id;

    $this
        ->withSession(membershipRecordsSession())
        ->actingAs($staff)
        ->get($baseUrl)
        ->assertOk()
        ->assertSee('beitritt.pdf')
        ->assertDontSee('Dokument hinzufügen');

    $this
        ->withSession(membershipRecordsSession())
        ->actingAs($staff)
        ->get($baseUrl.'/dokumente/'.$document->id)
        ->assertOk()
        ->assertDownload('beitritt.pdf');

    $this
        ->withSession(membershipRecordsSession())
        ->actingAs($staff)
        ->post($baseUrl.'/dokumente', [
            'document_type' => MembershipDocumentType::Other->value,
            'document' => UploadedFile::fake()->create(
                'neu.pdf',
                10,
                'application/pdf',
            ),
        ])
        ->assertForbidden();

    expect(
        AuditEvent::query()
            ->where('event_key', AuditEventCatalog::MEMBERSHIP_DOCUMENT_DOWNLOADED)
            ->where('subject_id', $document->id)
            ->exists(),
    )->toBeTrue();
});

it('records revokes and re-records membership consents without losing history', function () {
    $admin = makeMembershipRecordsActor(
        RoleKey::Administration,
        'membership-records-admin-consent@example.test',
    );
    $membership = makeMembershipRecordsMembership(
        'membership-records-consent@example.test',
    );
    $baseUrl = 'http://my.vdb.test/verwaltung/mitgliedschaften/'.$membership->id;

    $this
        ->withSession(membershipRecordsSession())
        ->actingAs($admin)
        ->post($baseUrl.'/zustimmungen', [
            'consent_key' => 'foto.veroeffentlichung',
            'label' => 'Fotoveröffentlichung',
            'version' => '2026-01',
            'source' => 'paper',
            'granted_at' => '2026-09-01T10:30',
            'notes' => 'Schriftlich auf dem Aufnahmeantrag.',
        ])
        ->assertSessionHasNoErrors();

    $consent = MembershipConsent::query()->sole();
    expect($consent->isActive())->toBeTrue();

    $this
        ->withSession(membershipRecordsSession())
        ->actingAs($admin)
        ->post($baseUrl.'/zustimmungen', [
            'consent_key' => 'foto.veroeffentlichung',
            'label' => 'Fotoveröffentlichung',
            'version' => '2026-02',
            'source' => 'email',
            'granted_at' => '2026-09-02T10:30',
        ])
        ->assertSessionHasErrors('consent_key');

    $this
        ->withSession(membershipRecordsSession())
        ->actingAs($admin)
        ->post(
            $baseUrl.'/zustimmungen/'.$consent->id.'/widerrufen',
            ['reason' => 'Widerruf per E-Mail'],
        )
        ->assertSessionHasNoErrors();

    expect($consent->refresh()->isActive())
        ->toBeFalse()
        ->and($consent->revocation_reason)
        ->toBe('Widerruf per E-Mail');

    $this
        ->withSession(membershipRecordsSession())
        ->actingAs($admin)
        ->post($baseUrl.'/zustimmungen', [
            'consent_key' => 'foto.veroeffentlichung',
            'label' => 'Fotoveröffentlichung',
            'version' => '2026-02',
            'source' => 'email',
            'granted_at' => '2026-09-10T09:00',
        ])
        ->assertSessionHasNoErrors();

    expect(MembershipConsent::query()->count())
        ->toBe(2)
        ->and(
            MembershipConsent::query()
                ->where('consent_key', 'foto.veroeffentlichung')
                ->whereNull('revoked_at')
                ->count(),
        )
        ->toBe(1)
        ->and(
            AuditEvent::query()
                ->where('event_key', AuditEventCatalog::MEMBERSHIP_CONSENT_RECORDED)
                ->count(),
        )
        ->toBe(2)
        ->and(
            AuditEvent::query()
                ->where('event_key', AuditEventCatalog::MEMBERSHIP_CONSENT_REVOKED)
                ->count(),
        )
        ->toBe(1);
});

it('keeps membership consent writes admin-only while staff can read the history', function () {
    $staff = makeMembershipRecordsActor(
        RoleKey::AdministrationStaff,
        'membership-records-consent-staff@example.test',
    );
    $membership = makeMembershipRecordsMembership(
        'membership-records-consent-staff-person@example.test',
    );
    MembershipConsent::query()->create([
        'membership_id' => $membership->id,
        'consent_key' => 'newsletter',
        'label' => 'Vereinsinformationen',
        'version' => '1',
        'source' => 'paper',
        'granted_at' => now()->subDay(),
    ]);

    $baseUrl = 'http://my.vdb.test/verwaltung/mitgliedschaften/'.$membership->id;

    $this
        ->withSession(membershipRecordsSession())
        ->actingAs($staff)
        ->get($baseUrl)
        ->assertOk()
        ->assertSee('Vereinsinformationen')
        ->assertDontSee('Zustimmung erfassen')
        ->assertDontSee('Widerruf erfassen');

    $this
        ->withSession(membershipRecordsSession())
        ->actingAs($staff)
        ->post($baseUrl.'/zustimmungen', [
            'consent_key' => 'newsletter',
            'label' => 'Vereinsinformationen',
            'version' => '2',
            'source' => 'paper',
            'granted_at' => now()->format('Y-m-d\TH:i'),
        ])
        ->assertForbidden();
});
