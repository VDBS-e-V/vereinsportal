<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Mailings\Models\Mailing;
use App\Modules\Mailings\Models\MailingDelivery;
use App\Modules\Mailings\Services\Mailings;
use App\Modules\Mailings\Support\MailPlaceholders;
use App\Modules\Membership\Models\Membership;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

function phUser(RoleKey $role, array $person = []): User
{
    $p = Person::factory()->create($person);
    $user = User::factory()->create(['person_id' => $p->id, 'email' => $p->email]);
    RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);

    return $user;
}

it('knows the new placeholders for every kind of letter', function () {
    foreach (['general', 'members', 'team'] as $kind) {
        expect(array_keys(MailPlaceholders::definitions($kind)))->toContain('email', 'heute', 'jahr', 'abmelden');
    }

    expect(array_keys(MailPlaceholders::definitions('general')))->toContain('wochentag', 'tage_bis')
        ->and(array_keys(MailPlaceholders::definitions('team')))->not->toContain('wochentag')
        ->and(MailPlaceholders::unknown('{email} {heute} {jahr} {abmelden|nicht möglich} {tage_bis} {wochentag}', 'general'))->toBe([]);
});

it('computes weekday and days until the meeting', function () {
    $values = app(Mailings::class)->meetingValues(['date' => '2030-03-15']);
    $soon = app(Mailings::class)->meetingValues(['date' => now()->addDays(10)->format('Y-m-d')]);
    $none = app(Mailings::class)->meetingValues([]);

    expect($values['wochentag'])->toBe('Freitag')->and($soon['tage_bis'])->toBe('10')->and($none['wochentag'])->toBe('')->and($none['tage_bis'])->toBe('');
});

it('fills address, date, year and the unsubscribe link per recipient', function () {
    Mail::fake();
    Queue::fake();
    phUser(RoleKey::Team, ['first_name' => 'Tina', 'email' => 'tina@beispiel.example']);
    $this->actingAs(phUser(RoleKey::AdministrationStaff));

    $this->post(route('mailings.office.store'), ['groups' => ['team'], 'subject' => 'Hinweis {jahr}', 'body' => '<p>{email} am {heute} im Jahr {jahr}. Abbestellen: {abmelden|nicht möglich}</p>'])->assertRedirect();

    $mailing = Mailing::query()->firstOrFail();
    $delivery = MailingDelivery::query()->where('email', 'tina@beispiel.example')->firstOrFail();
    $rendered = app(Mailings::class)->renderDelivery($mailing, $delivery);

    expect($rendered['subject'])->toBe('Hinweis '.now()->year)
        ->and($rendered['html'])->toContain('tina@beispiel.example')->toContain(now()->format('d.m.Y'))->toContain('/abmelden/')->not->toContain('nicht möglich');
});

it('leaves the unsubscribe link empty in mandatory letters so the default text applies', function () {
    Mail::fake();
    Queue::fake();
    Membership::factory()->manual()->create(['first_name' => 'Mia', 'last_name' => 'Muster', 'email' => 'mia@beispiel.example']);
    $this->actingAs(phUser(RoleKey::BoardMember));

    $this->post(route('mailings.board.store'), ['groups' => ['members'], 'mandatory' => '1', 'subject' => 'Einladung', 'body' => '<p>Abbestellen: {abmelden|nicht möglich}. In {tage_bis} Tagen am {wochentag}.</p>', 'meeting_date' => now()->addDays(30)->format('Y-m-d')])->assertRedirect();

    $mailing = Mailing::query()->firstOrFail();
    $rendered = app(Mailings::class)->renderDelivery($mailing, MailingDelivery::query()->firstOrFail());

    expect($rendered['html'])->toContain('nicht möglich')->not->toContain('/abmelden/')->toContain('In 30 Tagen am '.now()->addDays(30)->locale('de')->isoFormat('dddd'));
});

it('offers groups, exclusions and people as searchable multiple selects', function () {
    phUser(RoleKey::Team, ['title' => null, 'name_addition' => null, 'first_name' => 'Olga', 'last_name' => 'Anders', 'email' => 'olga@beispiel.example']);
    $olga = User::query()->where('email', 'olga@beispiel.example')->firstOrFail();
    $this->actingAs(phUser(RoleKey::AdministrationStaff));

    $page = $this->get(route('mailings.office.create'))->assertOk();
    $html = $page->getContent();

    expect(substr_count($html, 'data-vdbs-multiselect-filter'))->toBe(3)
        ->and($html)->toContain('name="groups[]"')->toContain('name="exclude[]"')->toContain('data-people-url=');

    $this->post(route('mailings.office.draft'), ['groups' => ['team'], 'exclude' => ['board'], 'people' => ['u:'.$olga->id], 'subject' => 'Hinweis', 'body' => '<p>Hallo</p>']);
    $draft = Mailing::query()->firstOrFail();

    $this->get(route('mailings.office.create', ['entwurf' => $draft->id]))->assertOk()->assertSee('Olga Anders');
});
