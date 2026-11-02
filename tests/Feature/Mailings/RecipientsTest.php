<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Mailings\Models\Mailing;
use App\Modules\Mailings\Models\MailSuppression;
use App\Modules\Mailings\Services\MailRecipients;
use App\Modules\Mailings\Services\MailSuppressions;
use App\Modules\Mailings\Services\MailUnsubscribes;
use App\Modules\Membership\Models\Membership;
use App\Modules\Partners\Models\Partner;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

function rcUser(array $roles, array $person = []): User
{
    $p = Person::factory()->create($person);
    $user = User::factory()->create(['person_id' => $p->id, 'email' => $p->email]);

    foreach ($roles as $role) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

function rcEmails(array $list): array
{
    return collect($list)->pluck('email')->sort()->values()->all();
}

it('takes the groups minus the exclusions once per address', function () {
    rcUser([RoleKey::Team], ['first_name' => 'Tina', 'email' => 'tina@beispiel.example']);
    rcUser([RoleKey::Team], ['first_name' => 'Timo', 'email' => 'timo@beispiel.example']);
    rcUser([RoleKey::Team, RoleKey::BoardMember], ['first_name' => 'Vera', 'email' => 'vera@beispiel.example']);
    rcUser([RoleKey::School], ['first_name' => 'Sabine', 'email' => 'schule@beispiel.example']);

    $recipients = app(MailRecipients::class);

    expect(rcEmails($recipients->groups(['team', 'schools'])))->toBe(['schule@beispiel.example', 'timo@beispiel.example', 'tina@beispiel.example', 'vera@beispiel.example'])
        ->and(rcEmails($recipients->groups(['team', 'schools'], false, ['board'])))->toBe(['schule@beispiel.example', 'timo@beispiel.example', 'tina@beispiel.example'])
        ->and($recipients->groupsWithStats(['team'], false, ['board'])['excluded'])->toBe(1);
});

it('adds chosen people once, ignores unsubscribes for them but never suppressed addresses', function () {
    $team = rcUser([RoleKey::Team], ['first_name' => 'Tina', 'email' => 'tina@beispiel.example']);
    $membership = Membership::factory()->manual()->create(['first_name' => 'Mia', 'last_name' => 'Muster', 'email' => 'mia@beispiel.example']);
    $partner = Partner::factory()->create();
    $contact = $partner->contacts()->create(['name' => 'Frau Beispiel', 'email' => 'sekretariat@beispiel.example', 'is_primary' => true]);
    $noMail = $partner->contacts()->create(['name' => 'Ohne Adresse', 'is_primary' => false]);
    app(MailUnsubscribes::class)->unsubscribe('tina@beispiel.example', 'team_mailings');
    app(MailSuppressions::class)->suppress('mia@beispiel.example', MailSuppression::MANUAL, null, null);

    $recipients = app(MailRecipients::class);
    $tokens = ['u:'.$team->id, 'm:'.$membership->id, 'k:'.$contact->id, 'k:'.$noMail->id, 'k:'.$contact->id, 'x:1', 'u:999999'];

    expect(rcEmails($recipients->groups([], false, [], $tokens)))->toBe(['sekretariat@beispiel.example', 'tina@beispiel.example'])
        ->and(rcEmails($recipients->groups(['team'], false, [], ['u:'.$team->id])))->toBe([])
        ->and(array_column($recipients->describe($tokens), 'label'))->toBe([$team->person->fullName(), 'Mia Muster', 'Frau Beispiel']);
});

it('lets each role choose only the people it may address', function () {
    $staff = rcUser([RoleKey::AdministrationStaff]);
    $board = rcUser([RoleKey::BoardMember]);
    $recipients = app(MailRecipients::class);

    expect($recipients->allowedTokens(['u:1', 'm:2', 'k:3', 'foo', 'u:x'], $staff))->toBe(['u:1', 'k:3'])
        ->and($recipients->allowedTokens(['u:1', 'm:2', 'k:3'], $board))->toBe(['u:1', 'm:2', 'k:3']);
});

it('searches accounts and members by name parts and keeps the members from the office', function () {
    rcUser([RoleKey::Team], ['title' => null, 'name_addition' => null, 'first_name' => 'Zotina', 'last_name' => 'Teamerin', 'email' => 'tina@beispiel.example']);
    Membership::factory()->manual()->create(['first_name' => 'Zotinka', 'last_name' => 'Mitglied', 'email' => 'tinka@beispiel.example']);
    $board = rcUser([RoleKey::BoardMember]);
    $staff = rcUser([RoleKey::AdministrationStaff]);
    $recipients = app(MailRecipients::class);

    expect(collect($recipients->search('Zotin', $board))->pluck('label')->all())->toBe(['Zotina Teamerin', 'Zotinka Mitglied'])
        ->and(collect($recipients->search('zotina team', $staff))->pluck('label')->all())->toBe(['Zotina Teamerin'])
        ->and(collect($recipients->search('Zotinka', $staff))->pluck('label')->all())->toBe([])
        ->and($recipients->search('T', $board))->toBe([])
        ->and($recipients->search('%%', $board))->toBe([]);

    $this->actingAs($board)->getJson(route('mailings.board.people', ['q' => 'Zotinka']))->assertOk()->assertJsonPath('results.0.label', 'Zotinka Mitglied')->assertJsonPath('results.0.hint', 'Mitglied');
});

it('sends to chosen people without any group and stores the selection with the letter', function () {
    Mail::fake();
    Queue::fake();
    $team = rcUser([RoleKey::Team], ['first_name' => 'Tina', 'email' => 'tina@beispiel.example']);
    rcUser([RoleKey::Team], ['first_name' => 'Timo', 'email' => 'timo@beispiel.example']);
    $staff = rcUser([RoleKey::AdministrationStaff]);

    $this->actingAs($staff)->post(route('mailings.office.store'), ['people' => ['u:'.$team->id, 'm:5'], 'subject' => 'Hinweis', 'body' => '<p>Hallo {vorname}</p>'])->assertRedirect();

    $mailing = Mailing::query()->firstOrFail();
    expect($mailing->recipients_count)->toBe(1)->and($mailing->chosenPeople())->toBe(['u:'.$team->id])->and($mailing->groups)->toBe([]);
});

it('stores the exclusions in the draft and shows chips and the numbers in the form', function () {
    rcUser([RoleKey::Team], ['first_name' => 'Tina', 'email' => 'tina@beispiel.example']);
    rcUser([RoleKey::Team, RoleKey::BoardMember], ['first_name' => 'Vera', 'email' => 'vera@beispiel.example']);
    $staff = rcUser([RoleKey::AdministrationStaff]);
    $other = rcUser([RoleKey::Team], ['title' => null, 'name_addition' => null, 'first_name' => 'Olga', 'last_name' => 'Anders', 'email' => 'olga@beispiel.example']);
    $this->actingAs($staff);

    $this->post(route('mailings.office.draft'), ['groups' => ['team'], 'exclude' => ['board'], 'people' => ['u:'.$other->id], 'subject' => 'Hinweis', 'body' => '<p>Hallo</p>'])->assertRedirect();

    $draft = Mailing::query()->firstOrFail();
    expect($draft->excludedGroups())->toBe(['board'])->and($draft->chosenPeople())->toBe(['u:'.$other->id]);

    $this->get(route('mailings.office.create', ['entwurf' => $draft->id]))->assertOk()->assertSee('Olga Anders')->assertSee('1 durch „Außer“', false);
});

it('refuses a letter with neither group nor person', function () {
    $this->actingAs(rcUser([RoleKey::AdministrationStaff]))->post(route('mailings.office.store'), ['subject' => 'Hinweis', 'body' => '<p>Hallo</p>'])->assertSessionHasErrors('groups');
});

it('prefills the contacts of a school from the school page and checks the permission', function () {
    $partner = Partner::factory()->create();
    $partner->contacts()->create(['name' => 'Frau Beispiel', 'email' => 'sekretariat@beispiel.example', 'is_primary' => true]);
    $staff = rcUser([RoleKey::AdministrationStaff]);

    $this->actingAs($staff)->get(route('mailings.office.create', ['schule' => $partner->id]))->assertOk()->assertSee('Frau Beispiel');

    $this->actingAs(rcUser([RoleKey::Member]))->get(route('mailings.office.create', ['schule' => $partner->id]))->assertForbidden();
    $this->actingAs(rcUser([RoleKey::Team]))->get(route('mailings.office.people'))->assertForbidden();
});
