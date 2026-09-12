<?php

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Communication\Models\EmailDelivery;
use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\EmailTemplateVersion;
use App\Modules\Identity\Enums\RoleAssignmentSource;
use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\RoleAssignment;
use App\Modules\Identity\Models\User;
use Database\Seeders\PasswordResetEmailTemplateSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

function makePersonDirectoryActor(string $email): User
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
        ['key' => RoleKey::AdministrationStaff->value],
        [
            'name' => 'Verwaltung',
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

function personDirectorySession(): array
{
    return [
        'identity.session_version' => 1,
        'identity.account_validated_at' => now()->timestamp,
    ];
}

function makePersonDirectoryPerson(string $email = 'directory.person@example.test'): Person
{
    return Person::query()->create([
        'first_name' => 'Erika',
        'last_name' => 'Muster',
        'birth_date' => '1990-04-12',
        'email' => $email,
        'postal_code' => '12345',
        'city' => 'Musterstadt',
        'country_code' => 'DE',
    ]);
}

function attachPersonDirectoryUser(
    Person $person,
    UserStatus $status = UserStatus::Active,
): User {
    $user = User::query()->create([
        'person_id' => $person->id,
        'email' => $person->email,
        'password' => 'Sicher123!',
        'status' => $status,
        'session_version' => 2,
        'last_login_at' => now()->subHour(),
    ]);
    $user->email_verified_at = now()->subDay();
    $user->save();

    return $user->refresh();
}

function preparePersonDirectoryPasswordResetTemplate(): void
{
    test()->seed(PasswordResetEmailTemplateSeeder::class);

    $template = EmailTemplate::query()
        ->where('key', 'auth.password_reset')
        ->sole();

    $publisher = User::query()->create([
        'email' => 'person-directory-template-publisher@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    EmailTemplateVersion::query()->create([
        'email_template_id' => $template->id,
        'version' => 1,
        'subject' => 'Passwort zurücksetzen',
        'html' => <<<'HTML'
<p><a href="{{ reset_url }}">Passwort zurücksetzen</a></p>
<p>Gültig bis {{ expires_at }}</p>
HTML,
        'published_by_user_id' => $publisher->id,
        'published_at' => now(),
    ]);

    $template->update([
        'is_active' => true,
    ]);
}

it('shows the requested columns and icon actions in the person directory', function () {
    $actor = makePersonDirectoryActor('person-directory-list@example.test');
    $person = makePersonDirectoryPerson();
    $user = attachPersonDirectoryUser($person);

    $this
        ->withSession(personDirectorySession())
        ->actingAs($actor)
        ->get('http://my.vdb.test/verwaltung/personen')
        ->assertOk()
        ->assertSeeInOrder([
            'Status',
            'Name',
            'E-Mail',
            'Ort',
            'Letzte Anmeldung',
            'Aktionen',
        ])
        ->assertSee('Erika Muster')
        ->assertSee('directory.person@example.test')
        ->assertSee('12345 Musterstadt')
        ->assertSee($user->last_login_at->format('d.m.Y, H:i').' Uhr')
        ->assertSee('title="Ansehen"', false)
        ->assertSee('title="Bearbeiten"', false)
        ->assertSee('title="Passwort zurücksetzen"', false)
        ->assertSee('title="Konto sperren"', false)
        ->assertDontSee('<th>Geburtsdatum</th>', false)
        ->assertDontSee('<th>Portalzugang</th>', false);
});

it('requests a password reset from the person directory with the admin as audit actor', function () {
    Queue::fake();
    preparePersonDirectoryPasswordResetTemplate();

    $actor = makePersonDirectoryActor('person-directory-reset-admin@example.test');
    $person = makePersonDirectoryPerson('person-directory-reset@example.test');
    $user = attachPersonDirectoryUser($person);

    $this
        ->withSession(personDirectorySession())
        ->actingAs($actor)
        ->post(
            'http://my.vdb.test/verwaltung/personen/'.$person->id.'/passwort-zuruecksetzen',
        )
        ->assertRedirect(route('administration.persons.index'))
        ->assertSessionHas('status_type', 'success');

    expect(
        DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->exists(),
    )
        ->toBeTrue()
        ->and(
            EmailDelivery::query()
                ->where('recipient_email', $user->email)
                ->count(),
        )
        ->toBe(1);

    $audit = AuditEvent::query()
        ->where(
            'event_key',
            AuditEventCatalog::AUTH_PASSWORD_RESET_REQUESTED,
        )
        ->where('subject_id', $user->id)
        ->sole();

    expect($audit->actor_user_id)
        ->toBe($actor->id)
        ->and($audit->actor_context)
        ->toBe('administration_person_directory')
        ->and($audit->subject_type)
        ->toBe('user');
});

it('locks an active portal account from the person directory', function () {
    $actor = makePersonDirectoryActor('person-directory-lock-admin@example.test');
    $person = makePersonDirectoryPerson('person-directory-lock@example.test');
    $user = attachPersonDirectoryUser($person);
    $previousSessionVersion = $user->session_version;

    $this
        ->withSession(personDirectorySession())
        ->actingAs($actor)
        ->post(
            'http://my.vdb.test/verwaltung/personen/'.$person->id.'/konto-sperren',
        )
        ->assertRedirect(route('administration.persons.index'))
        ->assertSessionHas('status_type', 'success');

    $user->refresh();

    expect($user->status)
        ->toBe(UserStatus::Disabled)
        ->and($user->session_version)
        ->toBe($previousSessionVersion + 1);

    $audit = AuditEvent::query()
        ->where('event_key', AuditEventCatalog::ACCOUNT_DISABLED)
        ->where('subject_id', $user->id)
        ->sole();

    expect($audit->actor_user_id)->toBe($actor->id);
});

it('rejects account quick actions for a person without a portal account', function () {
    $actor = makePersonDirectoryActor('person-directory-no-account-admin@example.test');
    $person = makePersonDirectoryPerson('person-directory-no-account@example.test');

    $this
        ->withSession(personDirectorySession())
        ->actingAs($actor)
        ->post(
            'http://my.vdb.test/verwaltung/personen/'.$person->id.'/passwort-zuruecksetzen',
        )
        ->assertRedirect(route('administration.persons.index'))
        ->assertSessionHas('status_type', 'danger');

    $this
        ->withSession(personDirectorySession())
        ->actingAs($actor)
        ->post(
            'http://my.vdb.test/verwaltung/personen/'.$person->id.'/konto-sperren',
        )
        ->assertRedirect(route('administration.persons.index'))
        ->assertSessionHas('status_type', 'danger');
});
