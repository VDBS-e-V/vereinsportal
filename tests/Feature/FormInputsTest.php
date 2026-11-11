<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

function fiUser(RoleKey $role): User
{
    $p = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $p->id, 'email' => $p->email]);
    RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);

    return $user;
}

it('uses the telephone type on the access form and fitting limits on address fields', function () {
    $this->get(route('portal.access'))->assertOk()->assertSee('type="tel"', false);

    $this->actingAs(fiUser(RoleKey::BoardMember));
    $this->get(route('admin.people.create'))->assertOk()->assertSee('autocapitalize="characters"', false)->assertSee('name="postal_code"', false);
    $this->get(route('partners.create'))->assertOk()->assertSee('inputmode="numeric"', false);
});

it('offers the two kinds of a manual membership as radio buttons', function () {
    $this->actingAs(fiUser(RoleKey::BoardMember));

    $this->get(route('membership.create'))->assertOk()->assertSee('type="radio"', false)->assertSee('name="mode"', false);
});
