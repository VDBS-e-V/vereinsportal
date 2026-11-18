<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\Coordinations\Models\Coordination;
use App\Modules\Partners\Models\Partner;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

function coUser(RoleKey ...$roles): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    foreach ($roles as $role) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

it('lets administration, the office and the board manage coordinations, nobody else', function () {
    $this->get(route('coordinations.index'))->assertRedirect(route('login'));

    foreach ([RoleKey::Team, RoleKey::Member, RoleKey::Coordination, RoleKey::School] as $role) {
        $this->actingAs(coUser($role))->get(route('coordinations.index'))->assertForbidden();
    }

    foreach ([RoleKey::Administration, RoleKey::AdministrationStaff, RoleKey::BoardMember] as $role) {
        $this->actingAs(coUser($role))->get(route('coordinations.index'))->assertOk()->assertSee('Koordinationen');
    }
});

it('creates, renames and deactivates a coordination and writes the audit log', function () {
    $this->actingAs(coUser(RoleKey::AdministrationStaff));

    $this->post(route('coordinations.store'), ['name' => 'Neukölln', 'sort_order' => 2])->assertRedirect(route('coordinations.index'))->assertSessionHas('success');
    $coordination = Coordination::query()->firstOrFail();

    expect($coordination->name)->toBe('Neukölln')->and($coordination->active)->toBeTrue()->and($coordination->sort_order)->toBe(2);

    $this->put(route('coordinations.update', $coordination), ['name' => 'Berlin-Neukölln', 'sort_order' => 1, 'active' => '0'])->assertSessionHas('success');

    expect($coordination->fresh()->name)->toBe('Berlin-Neukölln')->and($coordination->fresh()->active)->toBeFalse()
        ->and(AuditEntry::query()->where('event', 'coordinations.saved')->count())->toBe(2);
});

it('validates the name and rejects a duplicate', function () {
    $this->actingAs(coUser(RoleKey::Administration));
    Coordination::query()->create(['name' => 'Mitte']);

    $this->post(route('coordinations.store'), ['name' => ''])->assertSessionHasErrors('name');
    $this->post(route('coordinations.store'), ['name' => 'Mitte'])->assertSessionHasErrors('name');
    expect(Coordination::query()->count())->toBe(1);
});

it('does not delete a coordination that still has schools', function () {
    $this->actingAs(coUser(RoleKey::Administration));
    $used = Coordination::query()->create(['name' => 'Mitte']);
    $free = Coordination::query()->create(['name' => 'Spandau']);
    Partner::factory()->create(['coordination_id' => $used->id]);

    $this->delete(route('coordinations.destroy', $used))->assertSessionHasErrors('coordination');
    $this->delete(route('coordinations.destroy', $free))->assertSessionHas('success');

    expect(Coordination::query()->pluck('name')->all())->toBe(['Mitte'])
        ->and(AuditEntry::query()->where('event', 'coordinations.deleted')->count())->toBe(1);
});

it('offers active coordinations as dropdown in the school form and saves the choice', function () {
    $coordinator = coUser(RoleKey::Coordination);
    $active = Coordination::query()->create(['name' => 'Neukölln']);
    Coordination::query()->create(['name' => 'Altbestand', 'active' => false]);

    $this->actingAs($coordinator)->get(route('partners.create'))->assertOk()->assertSee('Neukölln')->assertDontSee('Altbestand');

    $partner = Partner::factory()->create();
    $this->actingAs($coordinator)->get(route('partners.edit', $partner))->assertOk()->assertSee('Koordination');
    expect(Coordination::options())->toBe([$active->id => 'Neukölln']);
});
