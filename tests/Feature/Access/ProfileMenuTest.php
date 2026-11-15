<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Events\Models\Event;
use App\Modules\Events\Models\EventRegistration;
use App\Modules\Events\Models\EventSession;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

function pmUser(?RoleKey $role = null, array $person = []): User
{
    $p = Person::factory()->create($person);
    $user = User::factory()->create(['person_id' => $p->id, 'email' => $p->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

function pmHeader(string $html): string
{
    preg_match('/<header class="vdbs-header".*?<\/header>/s', $html, $matches);

    return $matches[0] ?? '';
}

it('builds the profile menu with head, account group, help group and logout', function () {
    $user = pmUser(RoleKey::Administration, ['first_name' => 'Erika', 'last_name' => 'Beispiel']);

    $header = pmHeader($this->actingAs($user)->get(route('account.home'))->assertOk()->getContent());
    $menu = substr($header, (int) strpos($header, 'vdbs-dropdown__head'));

    // Kopf mit Name und Rolle, danach die Gruppen in der Reihenfolge der Vorlage, durch Linien getrennt
    expect($menu)->toContain('Erika Beispiel')->toContain('Administration')
        ->and(substr_count($menu, 'class="vdbs-dropdown__divider"'))->toBe(3)
        ->and($menu)->toMatch('/Mein Profil.*Kontoeinstellungen.*Meine Tickets.*Kontakt.*FAQ.*Hilfe.*Abmelden/s')
        ->and($menu)->toContain('href="'.route('account.tickets').'"')->toContain('href="'.route('portal.contact').'"')->toContain('href="'.route('portal.faq').'"')->toContain('href="'.route('portal.about').'"');
});

it('keeps the profile out of the menu for school accounts', function () {
    $header = pmHeader($this->actingAs(pmUser(RoleKey::School))->get(route('account.home'))->assertOk()->getContent());

    expect($header)->not->toContain('>Mein Profil<')->toContain('Kontoeinstellungen')->toContain('Meine Tickets');
});

it('shows the four public pages as a flat start menu', function () {
    $header = pmHeader($this->get(route('home'))->assertOk()->getContent());
    $nav = substr($header, (int) strpos($header, 'aria-label="Seiten im Bereich Start"'));

    expect($nav)->toMatch('/Über das Portal.*Zugang zum Portal.*FAQ.*Kontakt/s')
        ->and($nav)->not->toContain('vdbs-main-nav__submenu')->not->toContain('vdbs-main-nav__item--has-submenu');
});

it('lists the own event registrations as tickets and nobody else’s', function () {
    $user = pmUser(RoleKey::Team);
    $event = Event::query()->create(['slug' => 'demokratietag', 'title' => 'Demokratietag Nord', 'status' => 'open', 'location' => 'Berlin']);
    EventSession::query()->create(['event_id' => $event->id, 'starts_at' => now()->addMonth()]);

    EventRegistration::query()->create(['event_id' => $event->id, 'name' => 'Ich', 'email' => mb_strtolower($user->email), 'ticket_code' => 'K7QM4XWD', 'source' => 'self', 'verified_at' => now()]);
    EventRegistration::query()->create(['event_id' => $event->id, 'name' => 'Warte', 'email' => mb_strtolower($user->email), 'ticket_code' => 'ABCD2345', 'source' => 'self', 'waitlisted' => true, 'verified_at' => now()]);
    EventRegistration::query()->create(['event_id' => $event->id, 'name' => 'Fremd', 'email' => 'fremd@example.org', 'ticket_code' => 'ZZZZ9999', 'source' => 'self', 'verified_at' => now()]);

    $this->get(route('account.tickets'))->assertRedirect(route('login'));

    $html = $this->actingAs($user)->get(route('account.tickets'))->assertOk()->getContent();

    expect($html)->toContain('Demokratietag Nord')->toContain('K7QM-4XWD')->toContain('Warteliste')->not->toContain('ZZZZ-9999')->not->toContain('ABCD-2345');

    $this->actingAs(pmUser(RoleKey::Member))->get(route('account.tickets'))->assertOk()->assertSee('Noch keine Tickets');
});
