<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    \App\Modules\QuickLinks\Models\RoleLink::query()->delete(); // die Ausgangs-Schnellzugriffe aus der Migration würden eine weitere Gruppe im Menü zeigen
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
        ->and($menu)->toContain('href="'.route('support.mine').'"')->toContain('href="'.route('portal.contact').'"')->toContain('href="'.route('portal.faq').'"')->toContain('href="'.route('portal.about').'"');
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

