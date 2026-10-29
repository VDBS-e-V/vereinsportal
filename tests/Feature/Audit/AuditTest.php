<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\Capability;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\AccessGovernance\Support\Capabilities;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Http\Controllers\AuditController;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

function auUser(?RoleKey $role = null, array $person = []): User
{
    $personModel = Person::factory()->create($person);
    $user = User::factory()->create(['person_id' => $personModel->id, 'email' => $personModel->email]);

    if ($role !== null) {
        RoleAssignment::query()->create([
            'user_id' => $user->id,
            'role_id' => Role::query()->where('key', $role->value)->value('id'),
            'source' => RoleAssignmentSource::Manual,
            'starts_at' => now()->subMinute(),
        ]);
    }

    return $user;
}

function auEntry(AuditEvent $event, array $attributes = []): AuditEntry
{
    return AuditEntry::query()->create($attributes + [
        'occurred_at' => now(),
        'event' => $event->value,
    ]);
}

// ---------- Eintrag schreiben ----------

it('records actor, subject, context and address with readable labels', function () {
    $actor = auUser(RoleKey::AdministrationStaff, ['first_name' => 'Sabine', 'last_name' => 'Schmidt']);
    $target = auUser(null, ['first_name' => 'Tom', 'last_name' => 'Team']);

    AuditLog::record(AuditEvent::UserDisabled, $actor, $target, ['grund' => 'Test', 'leer' => null, 'liste' => ['a', 'b']]);

    $entry = AuditEntry::query()->firstOrFail();

    expect($entry->event)->toBe('user.disabled')
        ->and($entry->actor_user_id)->toBe($actor->id)
        ->and($entry->actor_label)->toBe('Sabine Schmidt')
        ->and($entry->subject_type)->toBe('user')
        ->and($entry->subject_id)->toBe($target->id)
        ->and($entry->subject_label)->toBe('Tom Team')
        ->and($entry->context)->toBe(['grund' => 'Test', 'leer' => null, 'liste' => ['a', 'b']])
        ->and($entry->occurred_at)->not->toBeNull();
});

it('keeps the labels readable after the person was changed or deleted', function () {
    $staff = auUser(RoleKey::AdministrationStaff);
    $person = Person::factory()->create(['first_name' => 'Anna', 'last_name' => 'Alt']);

    AuditLog::record(AuditEvent::PersonDeleted, $staff, $person);
    $person->delete();

    expect(AuditEntry::query()->firstOrFail()->subject_label)->toBe('Anna Alt');
});

it('shortens long texts and drops objects from the context', function () {
    AuditLog::record(AuditEvent::RoleAssigned, null, null, ['text' => str_repeat('x', 900), 'objekt' => new stdClass]);

    $context = AuditEntry::query()->firstOrFail()->context;

    expect(mb_strlen($context['text']))->toBe(500)->and($context['objekt'])->toBeNull();
});

it('never lets a failing log entry break the action', function () {
    // Ungültiges UTF-8 lässt das Speichern des JSON-Kontexts scheitern.
    AuditLog::record(AuditEvent::Login, null, null, ['kaputt' => "\xB1\x31"]);

    expect(AuditEntry::query()->count())->toBe(0);
});

it('does not allow changing or deleting single entries', function () {
    $entry = auEntry(AuditEvent::Login);

    expect(fn () => $entry->update(['event' => 'x']))->toThrow(LogicException::class)
        ->and(fn () => $entry->delete())->toThrow(LogicException::class);
});

it('labels every event and assigns it to a known category', function () {
    foreach (AuditEvent::cases() as $event) {
        expect($event->label())->not->toBe('')
            ->and(AuditEvent::categories())->toHaveKey($event->category())
            ->and($event->tone())->toBeIn(['neutral', 'success', 'warning', 'error']);
    }
});

// ---------- Ereignisse der Anwendung ----------

it('logs successful and failed logins without storing unknown addresses', function () {
    $user = User::factory()->create(['email' => 'erika@example.org']);

    $this->post('/login', ['email' => 'erika@example.org', 'password' => 'falsch']);
    $this->post('/login', ['email' => 'unbekannt@example.org', 'password' => 'falsch']);
    $this->post('/login', ['email' => 'erika@example.org', 'password' => 'password']);

    $events = AuditEntry::query()->orderBy('id')->pluck('event')->all();

    expect($events)->toBe(['auth.login_failed', 'auth.login'])
        ->and(AuditEntry::query()->where('event', 'auth.login_failed')->value('subject_id'))->toBe($user->id)
        ->and(AuditEntry::query()->get()->pluck('subject_label')->implode(' '))->not->toContain('unbekannt@example.org');
});

it('logs account actions of the administration', function () {
    $staff = auUser(RoleKey::AdministrationStaff);
    $target = auUser(RoleKey::Member);

    $this->actingAs($staff)->post(route('admin.users.disable', $target));
    $this->post(route('admin.users.enable', $target));

    expect(AuditEntry::query()->orderBy('id')->pluck('event')->all())->toBe(['user.disabled', 'user.enabled'])
        ->and(AuditEntry::query()->first()->actor_user_id)->toBe($staff->id);
});

it('logs assigning and ending a role with the role name', function () {
    $staff = auUser(RoleKey::AdministrationStaff);
    $target = auUser();
    $roleId = Role::query()->where('key', 'team')->value('id');

    $this->actingAs($staff)->post(route('admin.users.roles.store', $target), ['role_id' => $roleId, 'comment' => 'Projekt'])->assertRedirect();

    $assigned = AuditEntry::query()->where('event', 'role.assigned')->firstOrFail();
    expect($assigned->context['rolle'])->toBe('Teamende')->and($assigned->context['kommentar'])->toBe('Projekt')->and($assigned->subject_id)->toBe($target->id);

    $assignment = $target->roleAssignments()->firstOrFail();
    $this->delete(route('admin.users.roles.destroy', [$target, $assignment]))->assertRedirect();

    expect(AuditEntry::query()->where('event', 'role.ended')->firstOrFail()->context['rolle'])->toBe('Teamende');
});

it('logs person changes with field names only and nothing without changes', function () {
    $staff = auUser(RoleKey::AdministrationStaff);
    $person = Person::factory()->create(['city' => 'Leipzig']);
    $input = [
        'first_name' => $person->first_name, 'last_name' => $person->last_name, 'birth_date' => $person->birth_date->format('Y-m-d'),
        'email' => $person->email, 'country_code' => 'DE', 'city' => 'Leipzig',
    ];

    $this->actingAs($staff)->put(route('admin.people.update', $person), $input);
    expect(AuditEntry::query()->where('event', 'person.updated')->count())->toBe(0);

    $this->put(route('admin.people.update', $person), ['city' => 'Dresden'] + $input);

    $entry = AuditEntry::query()->where('event', 'person.updated')->firstOrFail();
    expect($entry->context)->toBe(['felder' => ['city']])->and(json_encode($entry->context))->not->toContain('Dresden');
});

it('logs creating and deleting a person', function () {
    $staff = auUser(RoleKey::AdministrationStaff);

    $this->actingAs($staff)->post(route('admin.people.store'), [
        'first_name' => 'Erika', 'last_name' => 'Musterfrau', 'birth_date' => '1985-04-12', 'email' => 'erika@example.org', 'country_code' => 'DE',
    ]);
    $person = Person::query()->where('email', 'erika@example.org')->firstOrFail();
    $this->delete(route('admin.people.destroy', $person));

    expect(AuditEntry::query()->orderBy('id')->pluck('event')->all())->toBe(['person.created', 'person.deleted'])
        ->and(AuditEntry::query()->first()->subject_label)->toBe('Erika Musterfrau');
});

it('logs a password change without any secret', function () {
    $user = auUser();

    $this->actingAs($user)->put(route('account.password.update'), ['current_password' => 'password', 'password' => 'Neues-Passwort-2026', 'password_confirmation' => 'Neues-Passwort-2026']);

    $entry = AuditEntry::query()->where('event', 'account.password_changed')->firstOrFail();

    expect($entry->actor_user_id)->toBe($user->id)->and($entry->context)->toBeNull()
        ->and(json_encode($entry->getAttributes()))->not->toContain('Neues-Passwort');
});

// ---------- Zugriff ----------

it('sends guests to the login and forbids everyone without audit rights', function () {
    $entry = auEntry(AuditEvent::Login);

    $this->get(route('admin.audit.index'))->assertRedirect(route('login'));

    foreach ([RoleKey::Member, RoleKey::AdministrationStaff] as $role) {
        $this->actingAs(auUser($role))->get(route('admin.audit.index'))->assertForbidden();
        $this->get(route('admin.audit.show', $entry))->assertForbidden();
        $this->get(route('admin.audit.export'))->assertForbidden();
    }
});

it('lets the administration and the board read the log', function () {
    $entry = auEntry(AuditEvent::Login);

    foreach ([RoleKey::Administration, RoleKey::BoardMember] as $role) {
        $this->actingAs(auUser($role))->get(route('admin.audit.index'))->assertOk();
        $this->get(route('admin.audit.show', $entry))->assertOk();
    }

    expect(Capabilities::has(auUser(RoleKey::AdministrationStaff), Capability::AuditView))->toBeFalse();
});

it('shows the log link in the header only to those who may read it', function () {
    $this->actingAs(auUser(RoleKey::BoardMember))->get(route('home'))->assertSee('Admins');
    $this->actingAs(auUser(RoleKey::Member))->get(route('home'))->assertDontSee(route('admin.audit.index'), false);
});

// ---------- Liste ----------

it('lists newest first with event, actor, subject and a row link', function () {
    $admin = auUser(RoleKey::Administration);
    auEntry(AuditEvent::UserDisabled, ['occurred_at' => now()->subDay(), 'actor_label' => 'Alt Akteur', 'subject_label' => 'Alt Betroffen']);
    $new = auEntry(AuditEvent::UserEnabled, ['occurred_at' => now(), 'actor_label' => 'Neu Akteur', 'subject_label' => 'Neu Betroffen']);

    $this->actingAs($admin)->get(route('admin.audit.index'))
        ->assertOk()
        ->assertSeeInOrder(['Neu Akteur', 'Alt Akteur'])
        ->assertSee('Konto entsperrt')
        ->assertSee('class="vdbs-table__row-link" href="'.route('admin.audit.show', $new).'"', false);
});

it('filters by search, group, event, period and subject', function () {
    $admin = auUser(RoleKey::Administration);
    auEntry(AuditEvent::UserDisabled, ['occurred_at' => '2026-03-10 12:00:00', 'actor_label' => 'Anna Admin', 'subject_label' => 'Bernd Beispiel', 'subject_type' => 'user', 'subject_id' => 7]);
    auEntry(AuditEvent::PersonCreated, ['occurred_at' => '2026-05-10 12:00:00', 'actor_label' => 'Clara Chef', 'subject_label' => 'Dora Dummy', 'subject_type' => 'person', 'subject_id' => 9]);
    $this->actingAs($admin);

    $this->get(route('admin.audit.index', ['q' => 'Bernd']))->assertSee('Bernd Beispiel')->assertDontSee('Dora Dummy');
    $this->get(route('admin.audit.index', ['category' => 'people']))->assertSee('Dora Dummy')->assertDontSee('Bernd Beispiel');
    $this->get(route('admin.audit.index', ['event' => 'user.disabled']))->assertSee('Bernd Beispiel')->assertDontSee('Dora Dummy');
    $this->get(route('admin.audit.index', ['from' => '2026-04-01']))->assertSee('Dora Dummy')->assertDontSee('Bernd Beispiel');
    $this->get(route('admin.audit.index', ['to' => '2026-04-01']))->assertSee('Bernd Beispiel')->assertDontSee('Dora Dummy');
    $this->get(route('admin.audit.index', ['subject' => 'person:9']))->assertSee('Dora Dummy')->assertDontSee('Bernd Beispiel');
});

it('ignores invalid filter values and escapes wildcards in the search', function () {
    $admin = auUser(RoleKey::Administration);
    auEntry(AuditEvent::Login, ['actor_label' => 'Erika Muster']);

    $this->actingAs($admin)->get(route('admin.audit.index', ['category' => 'x', 'event' => 'y', 'from' => '31.12.2026', 'to' => '2026-02-31', 'subject' => 'role:1']))
        ->assertOk()->assertSee('Erika Muster');
    $this->get(route('admin.audit.index', ['q' => '%']))->assertOk()->assertDontSee('Erika Muster');
});

it('paginates with 25 entries per page and keeps the filters', function () {
    $admin = auUser(RoleKey::Administration);

    foreach (range(1, 30) as $i) {
        auEntry(AuditEvent::Login, ['actor_label' => 'Akteur '.$i]);
    }

    $this->actingAs($admin)->get(route('admin.audit.index', ['event' => 'auth.login']))
        ->assertOk()
        ->assertSee('30 Einträge')
        ->assertSee('event=auth.login', false);
});

// ---------- Einzelansicht und Verlauf ----------

it('shows the details of an entry with its context and links', function () {
    $admin = auUser(RoleKey::Administration);
    $target = auUser();
    $entry = auEntry(AuditEvent::RoleAssigned, [
        'actor_user_id' => $admin->id, 'actor_label' => 'Admin', 'subject_type' => 'user', 'subject_id' => $target->id, 'subject_label' => 'Ziel Person',
        'context' => ['rolle' => 'Teamende', 'bis' => null, 'felder' => ['a', 'b']], 'ip_address' => '203.0.113.9',
    ]);

    $this->actingAs($admin)->get(route('admin.audit.show', $entry))
        ->assertOk()
        ->assertSee('Rolle vergeben')
        ->assertSee('Teamende')
        ->assertSee('a, b')
        ->assertSee('203.0.113.9')
        ->assertSee(route('admin.users.show', $target), false);
});

it('shows the history of a user and a person on their detail pages', function () {
    $admin = auUser(RoleKey::Administration);
    $target = auUser();
    auEntry(AuditEvent::UserDisabled, ['subject_type' => 'user', 'subject_id' => $target->id, 'actor_label' => 'Admin Eins']);
    auEntry(AuditEvent::PersonUpdated, ['subject_type' => 'person', 'subject_id' => $target->person_id, 'actor_label' => 'Admin Zwei']);

    $this->actingAs($admin)->get(route('admin.users.show', $target))->assertSee('Konto gesperrt')->assertSee('Admin Eins')->assertDontSee('Admin Zwei')
        ->assertSee(route('admin.audit.index', ['subject' => 'user:'.$target->id]), false);
    $this->get(route('admin.people.show', $target->person))->assertSee('Person geändert')->assertSee('Admin Zwei')->assertDontSee('Admin Eins');
});

it('hides the history from those without audit rights', function () {
    $staff = auUser(RoleKey::AdministrationStaff);
    $target = auUser();
    auEntry(AuditEvent::UserDisabled, ['subject_type' => 'user', 'subject_id' => $target->id, 'actor_label' => 'Geheim Akteur']);

    $this->actingAs($staff)->get(route('admin.users.show', $target))->assertOk()->assertDontSee('Geheim Akteur');
});

// ---------- Export ----------

it('exports the filtered log as csv, protects against formulas and logs the export', function () {
    $admin = auUser(RoleKey::Administration);
    auEntry(AuditEvent::UserDisabled, ['actor_label' => '=HYPERLINK("x")', 'subject_label' => 'Bernd Beispiel', 'context' => ['rolle' => 'Team']]);
    auEntry(AuditEvent::PersonCreated, ['actor_label' => 'Clara Chef', 'subject_label' => 'Dora Dummy']);

    $response = $this->actingAs($admin)->get(route('admin.audit.export', ['category' => 'users']));
    $csv = $response->streamedContent();

    $response->assertOk()->assertDownload();
    expect($response->headers->get('Content-Type'))->toContain('text/csv')
        ->and(str_starts_with($csv, "\xEF\xBB\xBF"))->toBeTrue()
        ->and($csv)->toContain('Zeit;Ereignis;Gruppe;Akteur;Betroffen;Angaben;IP-Adresse')
        ->toContain('Konto gesperrt')
        ->toContain("'=HYPERLINK")
        ->toContain('rolle: Team')
        ->not->toContain('Dora Dummy');

    $export = AuditEntry::query()->where('event', 'audit.exported')->firstOrFail();
    expect($export->actor_user_id)->toBe($admin->id)->and($export->context['filter'])->toBe(['category' => 'users']);
});

it('prefixes cells that spreadsheets would read as formulas', function () {
    expect(AuditController::safeCell('=1+1'))->toBe("'=1+1")
        ->and(AuditController::safeCell('+49'))->toBe("'+49")
        ->and(AuditController::safeCell('-x'))->toBe("'-x")
        ->and(AuditController::safeCell('@a'))->toBe("'@a")
        ->and(AuditController::safeCell('Text'))->toBe('Text')
        ->and(AuditController::safeCell(''))->toBe('');
});

// ---------- Aufbewahrung ----------

it('prunes entries older than the retention period', function () {
    auEntry(AuditEvent::Login, ['occurred_at' => now()->subDays(800), 'actor_label' => 'Uralt']);
    auEntry(AuditEvent::Login, ['occurred_at' => now()->subDays(10), 'actor_label' => 'Frisch']);

    $this->artisan('audit:prune', ['--days' => 730])->assertSuccessful();

    expect(AuditEntry::query()->pluck('actor_label')->all())->toBe(['Frisch']);
});

it('refuses a retention period below thirty days', function () {
    auEntry(AuditEvent::Login, ['occurred_at' => now()->subDays(40)]);

    $this->artisan('audit:prune', ['--days' => 7])->assertFailed();

    expect(AuditEntry::query()->count())->toBe(1);
});
