<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Mailings\Models\Mailing;
use App\Modules\Mailings\Models\MailSegment;
use App\Modules\Mailings\Services\SegmentResolver;
use App\Modules\Onboarding\Models\TeamOnboarding;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Mail::fake();
});

function sgUser(RoleKey $role, array $person = []): User
{
    $p = Person::factory()->create($person);
    $user = User::factory()->create(['person_id' => $p->id, 'email' => $p->email]);
    RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);

    return $user;
}

function sgSegment(array $definition, array $visible = ['office'], string $name = 'Test'): MailSegment
{
    return MailSegment::query()->create(['name' => $name, 'definition' => $definition, 'visible_to' => $visible]);
}

it('resolves filters at the time of use and picks up newcomers without changing the segment', function () {
    $open = sgUser(RoleKey::Team, ['postal_code' => '10115']);
    $done = sgUser(RoleKey::Team, ['postal_code' => '10115']);
    TeamOnboarding::query()->create(['user_id' => $open->id, 'started_at' => now()]);
    TeamOnboarding::query()->create(['user_id' => $done->id, 'started_at' => now(), 'completed_at' => now()]);

    $segment = sgSegment(['groups' => ['team'], 'filters' => ['onboarding' => 'open']]);
    $emails = fn () => collect(app(SegmentResolver::class)->recipients($segment)['kept'])->pluck('email')->sort()->values()->all();

    expect($emails())->toBe([$open->email]);

    $newcomer = sgUser(RoleKey::Team);
    TeamOnboarding::query()->create(['user_id' => $newcomer->id, 'started_at' => now()]);

    expect($emails())->toBe(collect([$open->email, $newcomer->email])->sort()->values()->all());
});

it('combines filters with AND and values of one filter with OR', function () {
    $berlin = sgUser(RoleKey::Team, ['postal_code' => '10115', 'birth_date' => now()->subYears(25)->toDateString()]);
    $hamburg = sgUser(RoleKey::Team, ['postal_code' => '20095', 'birth_date' => now()->subYears(25)->toDateString()]);
    $oldBerlin = sgUser(RoleKey::Team, ['postal_code' => '10117', 'birth_date' => now()->subYears(70)->toDateString()]);
    $resolver = app(SegmentResolver::class);
    $emails = fn (array $filters) => collect($resolver->recipients(sgSegment(['groups' => ['team'], 'filters' => $filters], ['office'], 'S'.md5(json_encode($filters))))['kept'])->pluck('email')->sort()->values()->all();
    $sorted = fn (array $users) => collect($users)->pluck('email')->sort()->values()->all();

    expect($emails(['postal_prefix' => ['10']]))->toBe($sorted([$berlin, $oldBerlin]))
        ->and($emails(['postal_prefix' => ['10', '20']]))->toBe($sorted([$berlin, $hamburg, $oldBerlin]))
        ->and($emails(['postal_prefix' => ['10'], 'age_group' => ['18-29']]))->toBe([$berlin->email])
        ->and($emails(['age_group' => ['65+']]))->toBe([$oldBerlin->email])
        ->and($emails(['postal_prefix' => ['99']]))->toBe([]);
});

it('creates, previews, edits and deletes segments and shows the count', function () {
    $this->actingAs(sgUser(RoleKey::AdministrationStaff));
    sgUser(RoleKey::Team);
    sgUser(RoleKey::Team);

    $this->get(route('mailings.segments.index'))->assertOk();
    $this->get(route('mailings.segments.create'))->assertOk()->assertSee('Bundesland der Schule');

    $this->post(route('mailings.segments.preview'), ['name' => 'Alle Teamende', 'groups' => ['team'], 'visible_to' => ['office']])->assertOk()->assertSee('Vorschau: 2 Personen');
    expect(MailSegment::query()->count())->toBe(0);

    $this->post(route('mailings.segments.store'), ['name' => 'Alle Teamende', 'groups' => ['team'], 'visible_to' => ['office', 'coordination']])->assertRedirect();
    $segment = MailSegment::query()->firstOrFail();
    expect($segment->visible_to)->toBe(['office', 'coordination']);

    $this->post(route('mailings.segments.store'), ['name' => 'Alle Teamende', 'groups' => ['team'], 'visible_to' => ['office']])->assertSessionHasErrors('name');
    $this->post(route('mailings.segments.store'), ['name' => 'Leer', 'visible_to' => ['office']])->assertSessionHasErrors('groups');

    $this->get(route('mailings.segments.index'))->assertSee('Alle Teamende')->assertSee('2');
    $this->put(route('mailings.segments.update', $segment), ['name' => 'Teamende neu', 'groups' => ['team'], 'visible_to' => ['office'], 'postal_prefix' => '99'])->assertRedirect();
    $this->get(route('mailings.segments.edit', $segment))->assertOk()->assertSee('Vorschau: 0 Personen')->assertSee('niemanden');

    $this->post(route('mailings.segments.destroy-many'), ['ids' => [$segment->id]])->assertRedirect();
    expect(MailSegment::query()->count())->toBe(0);
});

it('lets only the board create and change segments with members', function () {
    $office = sgUser(RoleKey::AdministrationStaff);
    $this->actingAs($office)->post(route('mailings.segments.store'), ['name' => 'Mitglieder', 'groups' => ['members'], 'visible_to' => ['board']])->assertSessionHasErrors('groups.0');

    $members = sgSegment(['groups' => ['members']], ['board'], 'Mitglieder');
    $this->get(route('mailings.segments.edit', $members))->assertForbidden();
    $this->delete(route('mailings.segments.destroy', $members))->assertForbidden();

    $this->actingAs(sgUser(RoleKey::BoardMember))->post(route('mailings.segments.store'), ['name' => 'Mitglieder 2', 'groups' => ['members'], 'visible_to' => ['board', 'coordination']])->assertRedirect();
    expect(MailSegment::query()->where('name', 'Mitglieder 2')->value('visible_to'))->toBe(['board']);

    foreach ([RoleKey::Member, RoleKey::Team, RoleKey::Coordination] as $role) {
        $this->actingAs(sgUser($role))->get(route('mailings.segments.index'))->assertForbidden();
    }
});

it('offers only visible and permitted segments when writing and sends to the segment', function () {
    sgUser(RoleKey::Team);
    sgUser(RoleKey::Team);
    $team = sgSegment(['groups' => ['team']], ['office', 'coordination'], 'Alle Teamende');
    $boardOnly = sgSegment(['groups' => ['board']], ['board'], 'Nur Vorstand');
    $members = sgSegment(['groups' => ['members']], ['board', 'office'], 'Mitglieder');

    $office = sgUser(RoleKey::AdministrationStaff);
    $coordination = sgUser(RoleKey::Coordination);
    $resolver = app(SegmentResolver::class);

    expect($resolver->usableBy($office)->pluck('id')->all())->toBe([$team->id])
        ->and($resolver->usableBy($coordination)->pluck('id')->all())->toBe([$team->id])
        ->and($resolver->usableBy(sgUser(RoleKey::BoardMember))->pluck('id')->sort()->values()->all())->toBe(collect([$team->id, $boardOnly->id, $members->id])->sort()->values()->all());

    $this->actingAs($office)->get(route('mailings.office.create'))->assertOk()->assertSee('Alle Teamende')->assertSee('name="segments[]"', false)->assertDontSee('Nur Vorstand');

    $this->post(route('mailings.office.store'), ['segments' => [$team->id, $boardOnly->id], 'subject' => 'Hallo', 'body' => '<p>Hallo {vorname}</p>']);
    $mailing = Mailing::query()->latest('id')->firstOrFail();

    expect($mailing->chosenSegments())->toBe([$team->id])->and($mailing->recipients_count)->toBe(2);
});

it('recalculates at sending time and ignores a deleted segment in an old draft', function () {
    sgUser(RoleKey::Team);
    $segment = sgSegment(['groups' => ['team']], ['office'], 'Teamende');
    $office = sgUser(RoleKey::AdministrationStaff);

    $this->actingAs($office)->post(route('mailings.office.draft'), ['segments' => [$segment->id], 'subject' => 'Hallo', 'body' => '<p>Hallo</p>']);
    $draft = Mailing::query()->latest('id')->firstOrFail();

    sgUser(RoleKey::Team);
    $this->post(route('mailings.office.store'), ['draft_id' => $draft->id, 'segments' => [$segment->id], 'subject' => 'Hallo', 'body' => '<p>Hallo</p>']);
    expect($draft->fresh()->recipients_count)->toBe(2);

    $segment->delete();
    $stats = app(SegmentResolver::class)->combined([], false, [], [], [$segment->id]);
    expect($stats['kept'])->toBe([]);
});

it('lists the segments page in the navigation of the office and the board', function () {
    $this->actingAs(sgUser(RoleKey::AdministrationStaff))->get(route('office.home'))->assertOk()->assertSee('Verteiler');
    $this->actingAs(sgUser(RoleKey::BoardMember))->get(route('office.home'))->assertOk()->assertSee('Verteiler');
});

it('filters by competences and by the number of conducted seminars', function () {
    $keys = array_keys((array) config('portal.team.competences', []));
    $skilled = sgUser(RoleKey::Team);
    $other = sgUser(RoleKey::Team);
    \App\Modules\Seminars\Models\TeamProfile::query()->create(['user_id' => $skilled->id, 'competences' => [$keys[0]], 'experience' => 'x']);
    \App\Modules\Seminars\Models\TeamProfile::query()->create(['user_id' => $other->id, 'competences' => [], 'experience' => 'y']);

    foreach ([\App\Modules\Seminars\Enums\SeminarStatus::Done, \App\Modules\Seminars\Enums\SeminarStatus::Done] as $status) {
        $seminar = \App\Modules\Seminars\Models\Seminar::factory()->create(['status' => $status]);
        \App\Modules\Seminars\Models\SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'user_id' => $skilled->id, 'status' => 'confirmed']);
    }

    $upcoming = \App\Modules\Seminars\Models\Seminar::factory()->create(['status' => \App\Modules\Seminars\Enums\SeminarStatus::Scheduled]);
    \App\Modules\Seminars\Models\SeminarApplication::query()->create(['seminar_id' => $upcoming->id, 'user_id' => $other->id, 'status' => 'confirmed']);

    $resolver = app(SegmentResolver::class);
    $emails = fn (array $filters) => collect($resolver->recipients(sgSegment(['groups' => ['team'], 'filters' => $filters], ['office'], 'F'.md5(json_encode($filters))))['kept'])->pluck('email')->all();

    expect($emails(['competence' => [$keys[0]]]))->toBe([$skilled->email])
        ->and($emails(['seminars_done' => 2]))->toBe([$skilled->email])
        ->and($emails(['seminars_done' => 3]))->toBe([])
        ->and(\App\Modules\Mailings\Support\SegmentFilters::normalize(['seminars_done' => '5', 'competence' => ['gibtsnicht']]))->toBe(['seminars_done' => 5]);
});

it('fills the team placeholder with the names of the confirmed team', function () {
    $seminar = \App\Modules\Seminars\Models\Seminar::factory()->create(['status' => \App\Modules\Seminars\Enums\SeminarStatus::Scheduled]);
    $anna = sgUser(RoleKey::Team, ['first_name' => 'Anna', 'last_name' => 'Alt']);
    $ben = sgUser(RoleKey::Team, ['first_name' => 'Ben', 'last_name' => 'Bunt']);
    $declined = sgUser(RoleKey::Team, ['first_name' => 'Cora', 'last_name' => 'Cäsar']);

    foreach ([[$anna, 'confirmed'], [$ben, 'confirmed'], [$declined, 'declined']] as [$user, $status]) {
        \App\Modules\Seminars\Models\SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'user_id' => $user->id, 'status' => $status]);
    }

    $values = app(\App\Modules\Mailings\Services\Mailings::class)->seminarValues($seminar);

    expect($values['team'])->toContain('Anna Alt')->toContain('Ben Bunt')->not->toContain('Cora')
        ->and(\App\Modules\Mailings\Support\MailPlaceholders::unknown('Team: {team}', 'team'))->toBe([])
        ->and(\App\Modules\Mailings\Support\MailPlaceholders::unknown('Team: {team}', 'general'))->toBe(['team']);
});

it('offers imported contacts as a group with unsubscribe and filters them by the state of the personal access', function () {
    $office = sgUser(RoleKey::AdministrationStaff);
    $sent = \App\Modules\Contacts\Models\TeamContact::query()->create(['email' => 'gesendet@beispiel.example', 'first_name' => 'Gina', 'last_name' => 'Gesendet']);
    $used = \App\Modules\Contacts\Models\TeamContact::query()->create(['email' => 'benutzt@beispiel.example', 'first_name' => 'Ben', 'last_name' => 'Benutzt']);
    $none = \App\Modules\Contacts\Models\TeamContact::query()->create(['email' => 'neu@beispiel.example', 'first_name' => 'Nina', 'last_name' => 'Neu']);
    $account = sgUser(RoleKey::Team);
    \App\Modules\Contacts\Models\TeamContact::query()->create(['email' => $account->email, 'first_name' => 'Doppelt', 'last_name' => 'Konto']);

    foreach ([[$sent, null], [$used, now()]] as [$contact, $lastUsed]) {
        \App\Modules\Contacts\Models\AccessCredential::query()->create(['contact_id' => $contact->id, 'token_hash' => hash('sha256', 'a'.$contact->id), 'code_hash' => hash('sha256', 'b'), 'code_hint' => '12', 'sent_at' => now()->subDay(), 'last_used_at' => $lastUsed, 'expires_at' => now()->addMonth()]);
    }

    $resolver = app(SegmentResolver::class);
    $emails = fn (array $filters, string $name) => collect($resolver->recipients(sgSegment(['groups' => ['contacts'], 'filters' => $filters], ['office'], $name))['kept'])->pluck('email')->sort()->values()->all();

    expect($emails([], 'Alle'))->toBe(['benutzt@beispiel.example', 'gesendet@beispiel.example', 'neu@beispiel.example'])
        ->and($emails(['contact_access' => 'not_sent'], 'Neu'))->toBe(['neu@beispiel.example'])
        ->and($emails(['contact_access' => 'sent_unused'], 'Gesendet'))->toBe(['gesendet@beispiel.example'])
        ->and($emails(['contact_access' => 'used'], 'Benutzt'))->toBe(['benutzt@beispiel.example']);

    expect(array_keys(\App\Modules\Mailings\Support\MailGroups::allowedFor($office)))->toContain('contacts')
        ->and(array_keys(\App\Modules\Mailings\Support\MailGroups::allowedFor(sgUser(RoleKey::Coordination))))->not->toContain('contacts');

    app(\App\Modules\Mailings\Services\MailUnsubscribes::class)->unsubscribe('neu@beispiel.example', 'contact_mailings');
    expect(collect(app(\App\Modules\Mailings\Services\MailRecipients::class)->groups(['contacts']))->pluck('email')->all())->not->toContain('neu@beispiel.example');
});
