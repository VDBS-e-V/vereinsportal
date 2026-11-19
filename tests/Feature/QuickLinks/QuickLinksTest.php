<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\People\Models\Person;
use App\Modules\QuickLinks\Models\RoleLink;
use App\Modules\QuickLinks\Support\QuickLinks;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    RoleLink::query()->delete();
});

function qlUser(RoleKey ...$roles): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    foreach ($roles as $role) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

it('collects the most important links of all roles, without duplicates, at most four', function () {
    RoleLink::query()->insert([
        ['role_key' => 'member', 'label' => 'M1', 'url' => '/a', 'priority' => 1],
        ['role_key' => 'member', 'label' => 'M2', 'url' => '/b', 'priority' => 3],
        ['role_key' => 'team', 'label' => 'T1', 'url' => '/c', 'priority' => 2],
        ['role_key' => 'team', 'label' => 'T-doppelt', 'url' => '/a', 'priority' => 2],
        ['role_key' => 'team', 'label' => 'T3', 'url' => '/d', 'priority' => 4],
        ['role_key' => 'team', 'label' => 'T4', 'url' => '/e', 'priority' => 5],
        ['role_key' => 'coordination', 'label' => 'Fremd', 'url' => '/f', 'priority' => 1],
    ]);

    $links = QuickLinks::forUser(qlUser(RoleKey::Member, RoleKey::Team));

    expect($links->pluck('label')->all())->toBe(['M1', 'T1', 'M2', 'T3']);
});

it('shows the links in the profile menu', function () {
    RoleLink::query()->create(['role_key' => 'member', 'label' => 'Mein Wichtiges', 'url' => '/mitglieder', 'priority' => 1]);

    $this->actingAs(qlUser(RoleKey::Member))->get(route('account.home'))->assertOk()->assertSee('Wichtige Links')->assertSee('Mein Wichtiges');
});

it('lets administration edit three links per role and validates the addresses', function () {
    $this->actingAs(qlUser(RoleKey::Team))->get(route('role-links.edit'))->assertForbidden();

    $this->actingAs(qlUser(RoleKey::Administration));
    $this->get(route('role-links.edit'))->assertOk()->assertSee('Schnellzugriffe');

    $this->put(route('role-links.update'), ['links' => ['team' => [['label' => 'Kalender', 'url' => '/teamende', 'priority' => 2], ['label' => '', 'url' => '']]]])->assertSessionHasNoErrors();
    expect(RoleLink::query()->where('role_key', 'team')->count())->toBe(1);

    $this->put(route('role-links.update'), ['links' => ['team' => [['label' => 'X', 'url' => 'javascript:alert(1)']]]])->assertSessionHasErrors('links.team.0.url');
    $this->put(route('role-links.update'), ['links' => ['team' => [['label' => 'Nur Titel', 'url' => '']]]])->assertSessionHasErrors();
    $this->put(route('role-links.update'), ['links' => ['gibtsnicht' => [['label' => 'X', 'url' => '/x']]]])->assertSessionHasErrors('links');
});
