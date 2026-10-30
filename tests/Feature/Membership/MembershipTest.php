<?php

use App\Modules\Access\Models\User;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Membership\Enums\MembershipEventType;
use App\Modules\Membership\Enums\MembershipStatus;
use App\Modules\Membership\Models\Membership;
use App\Modules\Membership\Services\Memberships;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

function mbUser(?RoleKey $role = null, ?Person $person = null): User
{
    $person ??= Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

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

function mbBoard(): User
{
    return mbUser(RoleKey::BoardMember);
}

function mbMemberRole(User $user): ?RoleAssignment
{
    return RoleAssignment::query()
        ->where('user_id', $user->id)
        ->where('source_type', 'membership')
        ->first();
}

// ---------- Zugriff ----------

it('sends guests to the login and forbids everyone but the board', function () {
    $membership = Membership::factory()->create();

    $this->get(route('membership.index'))->assertRedirect(route('login'));

    foreach ([RoleKey::Member, RoleKey::AdministrationStaff, RoleKey::Administration] as $role) {
        $this->actingAs(mbUser($role))->get(route('membership.index'))->assertForbidden();
        $this->get(route('membership.show', $membership))->assertForbidden();
        $this->get(route('membership.create'))->assertForbidden();
        $this->post(route('membership.admit', $membership), ['starts_on' => '2026-01-01'])->assertForbidden();
    }

    $this->actingAs(mbBoard())->get(route('membership.index'))->assertOk();
});

it('shows the area Vorstand with the page Mitgliedschaften to the board only', function () {
    preg_match('/<header class="vdbs-header".*?<\/header>/s', $this->actingAs(mbBoard())->get(route('membership.index'))->getContent(), $header);

    expect($header[0])->toContain('aria-label="Seiten im Bereich Vorstand"')->toContain('>Mitgliedschaften<');

    preg_match('/<header class="vdbs-header".*?<\/header>/s', $this->actingAs(mbUser(RoleKey::Administration))->get(route('home'))->getContent(), $other);

    expect($other[0])->not->toContain('>Vorstand<')->not->toContain('>Mitgliedschaften<');
});

// ---------- Liste ----------

it('lists memberships with status, dates and clickable rows', function () {
    $board = mbBoard();
    $erika = Membership::factory()->create(['person_id' => Person::factory()->create(['first_name' => 'Erika', 'last_name' => 'Muster'])]);
    Membership::factory()->requested()->create(['person_id' => Person::factory()->create(['first_name' => 'Bernd', 'last_name' => 'Beispiel'])]);

    $this->actingAs($board)->get(route('membership.index'))
        ->assertOk()
        ->assertSee('Erika Muster')
        ->assertSee('Bernd Beispiel')
        ->assertSee('Beantragt')
        ->assertSee('class="vdbs-table__row-link" href="'.route('membership.show', $erika).'"', false);
});

it('filters by name and by the effective status', function () {
    $board = mbBoard();
    $active = Membership::factory()->create(['person_id' => Person::factory()->create(['last_name' => 'Aktivia'])]);
    Membership::factory()->create(['person_id' => Person::factory()->create(['last_name' => 'Endend']), 'ends_on' => now()->addMonth()->toDateString()]);
    Membership::factory()->create(['person_id' => Person::factory()->create(['last_name' => 'Vorbei']), 'ends_on' => now()->subDay()->toDateString()]);
    Membership::factory()->requested()->create(['person_id' => Person::factory()->create(['last_name' => 'Antragia'])]);
    Membership::factory()->rejected()->create(['person_id' => Person::factory()->create(['last_name' => 'Abgelehnia'])]);
    $this->actingAs($board);

    $this->get(route('membership.index', ['q' => 'Aktivia']))->assertSee('Aktivia')->assertDontSee('Antragia');
    $this->get(route('membership.index', ['status' => 'requested']))->assertSee('Antragia')->assertDontSee('Aktivia');
    $this->get(route('membership.index', ['status' => 'active']))->assertSee('Aktivia')->assertSee('Endend')->assertDontSee('Vorbei')->assertDontSee('Antragia');
    $this->get(route('membership.index', ['status' => 'ending']))->assertSee('Endend')->assertDontSee('Aktivia');
    $this->get(route('membership.index', ['status' => 'ended']))->assertSee('Vorbei')->assertDontSee('Aktivia');
    $this->get(route('membership.index', ['status' => 'rejected']))->assertSee('Abgelehnia')->assertDontSee('Aktivia');
    $this->get(route('membership.index', ['status' => 'unsinn', 'sort' => 'x']))->assertOk();
    expect($active->effectiveStatus())->toBe(MembershipStatus::Active);
});

// Das manuelle Erfassen mit Dokument und die Online-Wege stehen in MembershipManualTest und JoinTest.

// ---------- Aufnahme und Ablehnung ----------

it('admits a requested membership and gives the account the member role from the joining date', function () {
    $board = mbBoard();
    $person = Person::factory()->create();
    $account = mbUser(null, $person);
    $membership = Membership::factory()->requested()->create(['person_id' => $person->id]);

    $this->actingAs($board)->post(route('membership.admit', $membership), ['starts_on' => '2025-01-15', 'comment' => 'Vorstandsbeschluss'])->assertRedirect(route('membership.show', $membership));

    $membership->refresh();
    expect($membership->status)->toBe(MembershipStatus::Active)
        ->and($membership->starts_on->toDateString())->toBe('2025-01-15')
        ->and($membership->events()->where('type', MembershipEventType::Admitted->value)->exists())->toBeTrue();

    $assignment = mbMemberRole($account);
    expect($assignment)->not->toBeNull()
        ->and($assignment->source)->toBe(RoleAssignmentSource::Automatic)
        ->and($assignment->source_id)->toBe($membership->id)
        ->and($assignment->starts_at->toDateString())->toBe('2025-01-15')
        ->and($assignment->ends_at)->toBeNull()
        ->and($assignment->role->key)->toBe('member');
});

it('admits only requested memberships', function () {
    $membership = Membership::factory()->create();

    $this->actingAs(mbBoard())->post(route('membership.admit', $membership), ['starts_on' => '2025-01-15'])->assertSessionHas('warning');
});

it('rejects a request only with a reason and gives no role', function () {
    $person = Person::factory()->create();
    $account = mbUser(null, $person);
    $membership = Membership::factory()->requested()->create(['person_id' => $person->id]);
    $this->actingAs(mbBoard());

    $this->post(route('membership.reject', $membership), [])->assertSessionHasErrors('comment');
    expect($membership->fresh()->status)->toBe(MembershipStatus::Requested);

    $this->post(route('membership.reject', $membership), ['comment' => 'Unvollständig'])->assertSessionHas('success');

    expect($membership->fresh()->status)->toBe(MembershipStatus::Rejected)
        ->and($membership->events()->first()->comment)->toBe('Unvollständig')
        ->and(mbMemberRole($account))->toBeNull();
});

it('lets the person join again after a rejection or an ended membership', function () {
    $person = Person::factory()->create();
    Membership::factory()->rejected()->create(['person_id' => $person->id]);
    Membership::factory()->ended()->create(['person_id' => $person->id]);

    expect(app(Memberships::class)->hasOpenDuplicate($person->first_name, $person->last_name, $person->birth_date, null))->toBeFalse();

    Membership::factory()->requested()->create(['person_id' => $person->id]);

    expect(app(Memberships::class)->hasOpenDuplicate($person->first_name, $person->last_name, $person->birth_date, null))->toBeTrue();
});

// ---------- Austritt ----------

it('enters a future termination and ends the member role at the end of that day', function () {
    $person = Person::factory()->create();
    $account = mbUser(null, $person);
    $membership = Membership::factory()->create(['person_id' => $person->id]);
    app(\App\Modules\Membership\Services\MembershipRoles::class)->syncPerson($person);
    $end = now()->addMonths(2)->toDateString();

    $this->actingAs(mbBoard())->post(route('membership.terminate', $membership), ['ends_on' => $end, 'comment' => 'Kündigung'])->assertSessionHas('success');

    $membership->refresh();
    expect($membership->status)->toBe(MembershipStatus::Active)
        ->and($membership->isEnding())->toBeTrue()
        ->and($membership->ends_on->toDateString())->toBe($end)
        ->and(mbMemberRole($account)->ends_at->toDateString())->toBe($end)
        ->and(mbMemberRole($account)->ends_at->format('H:i'))->toBe('23:59');
});

it('treats a termination for today as still running until the end of the day', function () {
    $membership = Membership::factory()->create();

    $this->actingAs(mbBoard())->post(route('membership.terminate', $membership), ['ends_on' => today()->toDateString()])->assertSessionHas('success');

    expect($membership->fresh()->status)->toBe(MembershipStatus::Active)->and($membership->fresh()->effectiveStatus())->toBe(MembershipStatus::Active);
});

it('ends the membership at once when the termination lies in the past', function () {
    $person = Person::factory()->create();
    $account = mbUser(null, $person);
    $membership = Membership::factory()->create(['person_id' => $person->id, 'starts_on' => '2020-01-01']);
    app(\App\Modules\Membership\Services\MembershipRoles::class)->syncPerson($person);

    $this->actingAs(mbBoard())->post(route('membership.terminate', $membership), ['ends_on' => now()->subMonth()->toDateString()])->assertSessionHas('success');

    expect($membership->fresh()->status)->toBe(MembershipStatus::Ended)
        ->and($membership->events()->pluck('type')->map->value->all())->toContain('ended')
        ->and(mbMemberRole($account)->ends_at->isPast())->toBeTrue();
});

it('refuses a termination before the joining date and for memberships that are not running', function () {
    $membership = Membership::factory()->create(['starts_on' => '2025-06-01']);
    $this->actingAs(mbBoard());

    $this->post(route('membership.terminate', $membership), ['ends_on' => '2025-05-31'])->assertSessionHas('warning');
    expect($membership->fresh()->ends_on)->toBeNull();

    $this->post(route('membership.terminate', Membership::factory()->requested()->create()), ['ends_on' => '2030-01-01'])->assertSessionHas('warning');
    $this->post(route('membership.terminate', $membership), [])->assertSessionHasErrors('ends_on');
});

it('withdraws a termination and keeps the member role open ended', function () {
    $person = Person::factory()->create();
    $account = mbUser(null, $person);
    $membership = Membership::factory()->create(['person_id' => $person->id, 'ends_on' => now()->addMonth()->toDateString()]);
    app(\App\Modules\Membership\Services\MembershipRoles::class)->syncPerson($person);
    expect(mbMemberRole($account)->ends_at)->not->toBeNull();

    $this->actingAs(mbBoard())->post(route('membership.withdraw', $membership), ['comment' => 'Doch geblieben'])->assertSessionHas('success');

    expect($membership->fresh()->ends_on)->toBeNull()
        ->and(mbMemberRole($account)->ends_at)->toBeNull()
        ->and($membership->events()->first()->type)->toBe(MembershipEventType::TerminationWithdrawn);

    $this->post(route('membership.withdraw', $membership))->assertSessionHas('warning');
});

// ---------- Rolle ----------

it('gives the member role to an account that is created later and leaves manual roles alone', function () {
    $person = Person::factory()->create();
    $membership = Membership::factory()->create(['person_id' => $person->id, 'starts_on' => '2024-01-01']);

    $this->actingAs(mbUser(RoleKey::AdministrationStaff, null))->post(route('admin.people.account', $person))->assertRedirect();

    $account = $person->fresh()->user;
    expect(mbMemberRole($account))->not->toBeNull()
        ->and(mbMemberRole($account)->source_id)->toBe($membership->id)
        ->and($account->roleAssignments()->where('source', 'manual')->count())->toBe(0);
});

it('does nothing for people without an account and is idempotent', function () {
    $person = Person::factory()->create();
    Membership::factory()->create(['person_id' => $person->id]);
    $roles = app(\App\Modules\Membership\Services\MembershipRoles::class);

    $roles->syncPerson($person);
    expect(RoleAssignment::query()->count())->toBe(0);

    $account = mbUser(null, $person);
    $roles->syncPerson($person);
    $roles->syncPerson($person);

    expect(RoleAssignment::query()->where('user_id', $account->id)->count())->toBe(1);
});

it('gives the account the capabilities of the member role only while the membership runs', function () {
    $person = Person::factory()->create();
    $account = mbUser(null, $person);
    $membership = Membership::factory()->create(['person_id' => $person->id]);
    app(\App\Modules\Membership\Services\MembershipRoles::class)->syncPerson($person);

    expect($account->activeRoleAssignments()->count())->toBe(1);

    app(Memberships::class)->terminate($membership->fresh(), Carbon::now()->subDays(2), mbBoard());

    expect($account->activeRoleAssignments()->count())->toBe(0);
});

// ---------- Verlauf und Datenschutz ----------

it('keeps a history with actor and comment, newest first, and accepts notes', function () {
    $board = mbBoard();
    $membership = Membership::factory()->requested()->create();
    $this->actingAs($board);

    $this->post(route('membership.note', $membership), [])->assertSessionHasErrors('note');
    $this->post(route('membership.note', $membership), ['note' => 'Unterlagen nachgereicht'])->assertSessionHas('success');
    $this->post(route('membership.admit', $membership), ['starts_on' => '2025-02-01', 'comment' => 'Beschluss 12/2025']);

    $types = $membership->events()->pluck('type')->map->value->all();
    // Mit dem Aufnehmen geht automatisch das Willkommenspaket raus (steht ebenfalls im Verlauf)
    expect($types)->toBe(['welcome_sent', 'admitted', 'note']);

    $this->get(route('membership.show', $membership))
        ->assertOk()
        ->assertSeeInOrder(['Aufgenommen', 'Beschluss 12/2025', 'Notiz', 'Unterlagen nachgereicht'])
        ->assertSee(e($membership->events()->first()->actor_label), false);
});

it('shows the right actions for each status', function () {
    $this->actingAs(mbBoard());

    $this->get(route('membership.show', Membership::factory()->requested()->create()))->assertSee('data-vdbs-dialog-open="admit"', false)->assertSee('data-vdbs-dialog-open="reject"', false)->assertDontSee('data-vdbs-dialog-open="terminate"', false);
    $this->get(route('membership.show', Membership::factory()->create()))->assertSee('data-vdbs-dialog-open="terminate"', false)->assertDontSee('data-vdbs-dialog-open="admit"', false)->assertDontSee('data-vdbs-dialog-open="withdraw"', false);
    $this->get(route('membership.show', Membership::factory()->create(['ends_on' => now()->addMonth()->toDateString()])))->assertSee('data-vdbs-dialog-open="withdraw"', false);
    $this->get(route('membership.show', Membership::factory()->ended()->create()))->assertDontSee('data-vdbs-dialog-open="terminate"', false)->assertDontSee('data-vdbs-dialog-open="admit"', false);
});

it('keeps membership data out of the general log and the person pages', function () {
    $board = mbBoard();
    $person = Person::factory()->create(['first_name' => 'Erika', 'last_name' => 'Muster']);
    $membership = Membership::factory()->requested()->create(['person_id' => $person->id]);
    mbUser(null, $person);
    $this->actingAs($board);

    $this->post(route('membership.admit', $membership), ['starts_on' => '2025-01-01', 'comment' => 'Geheimer Kommentar']);
    $this->post(route('membership.terminate', $membership), ['ends_on' => now()->addMonth()->toDateString()]);

    expect(AuditEntry::query()->pluck('event')->filter(fn ($event) => str_contains($event, 'member'))->all())->toBe([])
        ->and(AuditEntry::query()->get()->toJson())->not->toContain('Geheimer Kommentar');

    $this->get(route('admin.people.show', $person))->assertOk()->assertDontSee('Mitgliedschaft')->assertDontSee('Mitglied seit');
});

it('does not delete people with a membership', function () {
    $person = Person::factory()->create();
    Membership::factory()->ended()->create(['person_id' => $person->id]);

    $this->actingAs(mbBoard())->delete(route('admin.people.destroy', $person))->assertSessionHas('warning');

    expect(Person::query()->whereKey($person->id)->exists())->toBeTrue();
});

// ---------- Konto ----------

it('shows members their own membership in the profile', function () {
    $person = Person::factory()->create();
    $account = mbUser(null, $person);
    Membership::factory()->create(['person_id' => $person->id, 'starts_on' => '2023-05-01']);

    $this->actingAs($account)->get(route('account.profile'))->assertOk()->assertSee('Mitgliedschaft')->assertSee('01.05.2023')->assertSee('Aktiv');

    $this->actingAs(mbUser())->get(route('account.profile'))->assertOk()->assertDontSee('Mitglied seit');
});

// ---------- Tagesabgleich ----------

it('ends memberships whose last day has passed with the daily sync', function () {
    $person = Person::factory()->create();
    $account = mbUser(null, $person);
    $past = Membership::factory()->create(['person_id' => $person->id, 'ends_on' => now()->subDay()->toDateString()]);
    $future = Membership::factory()->create(['ends_on' => now()->addDay()->toDateString()]);

    $this->artisan('membership:sync')->assertSuccessful();

    expect($past->fresh()->status)->toBe(MembershipStatus::Ended)
        ->and($future->fresh()->status)->toBe(MembershipStatus::Active)
        ->and($past->events()->first()->type)->toBe(MembershipEventType::Ended)
        ->and($past->events()->first()->actor_label)->toBe('automatisch')
        ->and(mbMemberRole($account)->ends_at->isPast())->toBeTrue();

    $this->artisan('membership:sync')->assertSuccessful();
    expect($past->events()->count())->toBe(1);
});

it('derives the effective status from the last day', function () {
    expect(Membership::factory()->make(['ends_on' => today()])->effectiveStatus())->toBe(MembershipStatus::Active)
        ->and(Membership::factory()->make(['ends_on' => today()->subDay()])->effectiveStatus())->toBe(MembershipStatus::Ended)
        ->and(Membership::factory()->make(['ends_on' => today()->addDay()])->isEnding())->toBeTrue()
        ->and(Membership::factory()->make()->isEnding())->toBeFalse();
});
