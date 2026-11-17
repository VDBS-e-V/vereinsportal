<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\NavigationControl\Models\NavigationHidden;
use App\Modules\NavigationControl\Support\NavigationControl;
use App\Modules\People\Models\Person;
use App\Support\PortalAreas;
use App\Support\PortalNavigation;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    NavigationHidden::query()->delete();
    NavigationControl::reset();
});

function ncUser(RoleKey ...$roles): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email, 'email_verified_at' => now()]);

    foreach ($roles as $role) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

it('shows every area and page until something is hidden', function () {
    $user = ncUser(RoleKey::Administration);

    expect(PortalNavigation::visibleAreas($user))->toContain(PortalAreas::Admins);
});

it('hides an area and a page from the menus without touching the permissions', function () {
    $user = ncUser(RoleKey::BoardMember);
    expect(PortalNavigation::visibleAreas($user))->toContain(PortalAreas::Vorstand);

    NavigationControl::replace(['area:vorstand']);
    expect(PortalNavigation::visibleAreas($user))->not->toContain(PortalAreas::Vorstand);

    NavigationControl::replace(['page:vorstand:board.reports']);
    $labels = collect(PortalNavigation::pages(PortalAreas::Vorstand, $user))->pluck('label');
    expect($labels)->not->toContain('Berichte')->and($labels)->toContain('Sitzungen');
});

it('lets only administration edit the navigation and saves what stays visible', function () {
    $this->actingAs(ncUser(RoleKey::Member))->get(route('navigation.edit'))->assertForbidden();

    $admin = ncUser(RoleKey::Administration);
    $this->actingAs($admin)->get(route('navigation.edit'))->assertOk()->assertSee('Koordination');

    // Nur Bereich Vorstand angehakt: alle anderen werden ausgeblendet
    $this->actingAs($admin)->put(route('navigation.update'), ['areas' => ['vorstand' => '1']])->assertRedirect(route('navigation.edit'));

    expect(NavigationHidden::query()->where('key', 'area:vorstand')->exists())->toBeFalse()
        ->and(NavigationHidden::query()->where('key', 'area:koordination')->exists())->toBeTrue()
        ->and(NavigationHidden::query()->where('key', 'area:admins')->exists())->toBeFalse();
});
