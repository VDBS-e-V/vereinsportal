<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Mailings\Models\Mailing;
use App\Modules\Mailings\Services\MailRecipients;
use App\Modules\Mailings\Support\MailGroups;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Mail::fake();
});

function rmUser(RoleKey $role): User
{
    $p = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $p->id, 'email' => $p->email]);
    RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);

    return $user;
}

it('follows the rights matrix for the groups of each role', function () {
    $board = array_keys(MailGroups::allowedFor(rmUser(RoleKey::BoardMember)));
    $office = array_keys(MailGroups::allowedFor(rmUser(RoleKey::AdministrationStaff)));
    $coordination = array_keys(MailGroups::allowedFor(rmUser(RoleKey::Coordination)));

    expect($board)->toEqualCanonicalizing(['members', 'team', 'schools', 'coordination', 'education', 'board', 'office', 'contacts'])
        ->and($office)->toEqualCanonicalizing(['team', 'schools', 'coordination', 'education', 'board', 'office', 'contacts'])
        ->and($coordination)->toEqualCanonicalizing(['team', 'schools', 'coordination', 'education']);

    foreach ([RoleKey::Member, RoleKey::Team, RoleKey::Administration] as $role) {
        expect(MailGroups::allowedFor(rmUser($role)))->toBe([]);
    }
});

it('lets the coordination choose only accounts of teams and schools as single people', function () {
    $coordination = rmUser(RoleKey::Coordination);
    $team = rmUser(RoleKey::Team);
    $board = rmUser(RoleKey::BoardMember);
    $recipients = app(MailRecipients::class);

    expect($recipients->allowedTokens(['u:'.$team->id, 'u:'.$board->id, 'm:1', 'e:x@example.org'], $coordination))->toBe(['u:'.$team->id])
        ->and($recipients->allowedTokens(['u:'.$board->id, 'e:x@example.org'], rmUser(RoleKey::AdministrationStaff)))->toBe(['u:'.$board->id, 'e:x@example.org']);
});

it('gives the coordination its own page, hides members and needs no approval', function () {
    config(['portal.mailings.approval.threshold' => 1]);
    $coordination = rmUser(RoleKey::Coordination);
    rmUser(RoleKey::Team);

    $this->actingAs($coordination);
    $this->get(route('mailings.coordination.index'))->assertOk()->assertSee('Rundschreiben');
    $this->get(route('mailings.coordination.create'))->assertOk()->assertSee('Teamende')->assertDontSee('Mitglieder (');
    $this->get(route('coordination.home'))->assertOk()->assertSee('Rundschreiben');
    $this->get(route('mailings.coordination.people', ['q' => 'zz']))->assertOk();

    $this->post(route('mailings.coordination.store'), ['groups' => ['members'], 'subject' => 'Hallo', 'body' => '<p>Hallo</p>'])->assertSessionHasErrors('groups.0');

    $this->post(route('mailings.coordination.store'), ['groups' => ['team'], 'subject' => 'Hallo', 'body' => '<p>Hallo {vorname}</p>']);
    $mailing = Mailing::query()->latest('id')->firstOrFail();
    expect($mailing->sender_area)->toBe('coordination')->and($mailing->status)->not->toBe(Mailing::STATUS_PENDING);

    $this->get(route('mailings.coordination.show', $mailing))->assertOk();

    $office = rmUser(RoleKey::AdministrationStaff);
    $this->actingAs($office)->post(route('mailings.office.store'), ['groups' => ['team'], 'subject' => 'Hallo', 'body' => '<p>Hallo {vorname}</p>']);
    $officeMailing = Mailing::query()->latest('id')->firstOrFail();
    expect($officeMailing->status)->toBe(Mailing::STATUS_PENDING);

    $this->actingAs($coordination)->get(route('mailings.coordination.show', $officeMailing))->assertNotFound();
});

it('keeps the coordination page from everyone else', function () {
    foreach ([RoleKey::Member, RoleKey::Team, RoleKey::BoardMember, RoleKey::AdministrationStaff] as $role) {
        $this->actingAs(rmUser($role))->get(route('mailings.coordination.index'))->assertForbidden();
    }
});
