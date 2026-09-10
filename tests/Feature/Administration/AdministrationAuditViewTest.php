<?php

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Enums\RoleAssignmentSource;
use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\RoleAssignment;
use App\Modules\Identity\Models\User;

function makeAuditViewActor(RoleKey $roleKey, string $email): User
{
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
            'name' => $roleKey->name,
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

function auditViewSession(): array
{
    return [
        'identity.session_version' => 1,
        'identity.account_validated_at' => now()->timestamp,
    ];
}

it('allows administration to browse audit events and blocks administration staff', function () {
    $admin = makeAuditViewActor(
        RoleKey::Administration,
        'audit-admin@example.test',
    );
    $staff = makeAuditViewActor(
        RoleKey::AdministrationStaff,
        'audit-staff@example.test',
    );

    $event = app(AuditWriter::class)->write(
        eventKey: AuditEventCatalog::AUTH_PASSWORD_CHANGED,
        actorType: AuditActorType::User,
        actorUserId: $admin->id,
        subjectType: 'user',
        subjectId: $admin->id,
    );

    $this
        ->withSession(auditViewSession())
        ->actingAs($admin)
        ->get('http://my.vdb.test/verwaltung/audit')
        ->assertOk()
        ->assertSee(AuditEventCatalog::AUTH_PASSWORD_CHANGED);

    $this
        ->withSession(auditViewSession())
        ->actingAs($admin)
        ->get('http://my.vdb.test/verwaltung/audit/'.$event->id)
        ->assertOk();

    $this
        ->withSession(auditViewSession())
        ->actingAs($staff)
        ->get('http://my.vdb.test/verwaltung/audit')
        ->assertForbidden();

    $this
        ->withSession(auditViewSession())
        ->actingAs($staff)
        ->get('http://my.vdb.test/verwaltung/audit/'.$event->id)
        ->assertForbidden();
});

it('filters audit events by event actor subject and date range', function () {
    $admin = makeAuditViewActor(
        RoleKey::Administration,
        'audit-filter-admin@example.test',
    );
    $otherActor = User::query()->create([
        'email' => 'audit-other-actor@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 1,
    ]);
    $person = Person::query()->create([
        'first_name' => 'Filter',
        'last_name' => 'Person',
        'birth_date' => '1990-01-01',
        'email' => 'audit-filter-person@example.test',
        'country_code' => 'DE',
    ]);

    app(AuditWriter::class)->write(
        eventKey: AuditEventCatalog::PERSON_UPDATED,
        actorType: AuditActorType::User,
        actorUserId: $admin->id,
        subjectType: 'person',
        subjectId: $person->id,
        oldValues: ['email' => 'old-filter@example.test'],
        newValues: ['email' => 'new-filter@example.test'],
        occurredAt: now()->subDay(),
    );

    app(AuditWriter::class)->write(
        eventKey: AuditEventCatalog::AUTH_PASSWORD_CHANGED,
        actorType: AuditActorType::User,
        actorUserId: $otherActor->id,
        subjectType: 'user',
        subjectId: $otherActor->id,
        occurredAt: now()->subDays(10),
    );

    $url = 'http://my.vdb.test/verwaltung/audit?'.http_build_query([
        'event_key' => AuditEventCatalog::PERSON_UPDATED,
        'actor_id' => $admin->id,
        'subject_type' => 'person',
        'subject_id' => $person->id,
        'from' => now()->subDays(2)->toDateString(),
        'to' => now()->toDateString(),
    ]);

    $this
        ->withSession(auditViewSession())
        ->actingAs($admin)
        ->get($url)
        ->assertOk()
        ->assertSee(AuditEventCatalog::PERSON_UPDATED)
        ->assertViewHas('events', function ($events): bool {
            return $events->total() === 1
                && $events->first()?->event_key === AuditEventCatalog::PERSON_UPDATED;
        });
});

it('shows whitelisted audit values and subject link but hides technical metadata', function () {
    $admin = makeAuditViewActor(
        RoleKey::Administration,
        'audit-detail-admin@example.test',
    );
    $person = Person::query()->create([
        'first_name' => 'Audit',
        'last_name' => 'Detail',
        'birth_date' => '1985-05-20',
        'email' => 'audit-detail@example.test',
        'country_code' => 'DE',
    ]);

    $event = app(AuditWriter::class)->write(
        eventKey: AuditEventCatalog::PERSON_UPDATED,
        actorType: AuditActorType::User,
        actorUserId: $admin->id,
        subjectType: 'person',
        subjectId: $person->id,
        oldValues: ['email' => 'old-visible@example.test'],
        newValues: ['email' => 'new-visible@example.test'],
        comment: 'Fachliche Korrektur',
        ipAddress: '203.0.113.77',
        userAgent: 'SensitiveBrowser/9.9',
        deviceInfo: ['fingerprint' => 'secret-device-token'],
    );

    $this
        ->withSession(auditViewSession())
        ->actingAs($admin)
        ->get('http://my.vdb.test/verwaltung/audit/'.$event->id)
        ->assertOk()
        ->assertSee('old-visible@example.test')
        ->assertSee('new-visible@example.test')
        ->assertSee('Fachliche Korrektur')
        ->assertSee('Person #'.$person->id)
        ->assertSee(route('administration.persons.show', $person), false)
        ->assertDontSee('203.0.113.77')
        ->assertDontSee('SensitiveBrowser/9.9')
        ->assertDontSee('secret-device-token');
});

it('does not expose audit navigation to administration staff', function () {
    $admin = makeAuditViewActor(
        RoleKey::Administration,
        'audit-navigation-admin@example.test',
    );
    $staff = makeAuditViewActor(
        RoleKey::AdministrationStaff,
        'audit-navigation-staff@example.test',
    );

    $this
        ->withSession(auditViewSession())
        ->actingAs($admin)
        ->get('http://my.vdb.test/verwaltung')
        ->assertOk()
        ->assertSee('Audit-Protokoll');

    $this
        ->withSession(auditViewSession())
        ->actingAs($staff)
        ->get('http://my.vdb.test/verwaltung')
        ->assertOk()
        ->assertDontSee('Audit-Protokoll')
        ->assertDontSee('>Audit<', false);
});