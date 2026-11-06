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
        ->and($resolver->usableBy(sgUser(RoleKey::BoardMember))->pluck('id')->sort()->values()->all())->toBe(collect([$boardOnly->id, $members->id])->sort()->values()->all());

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
