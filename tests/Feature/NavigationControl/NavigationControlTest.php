<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\NavigationControl\Models\NavigationArea;
use App\Modules\NavigationControl\Models\NavigationItem;
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
    NavigationArea::query()->delete();
    NavigationItem::query()->delete();
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

it('shows every area until something is changed', function () {
    expect(PortalNavigation::visibleAreas(ncUser(RoleKey::Administration)))->toContain(PortalAreas::Admins);
});

it('hides an area from the header and the lists without touching the permissions', function () {
    $user = ncUser(RoleKey::BoardMember);
    expect(PortalNavigation::visibleAreas($user))->toContain(PortalAreas::Geschaeftsstelle);

    NavigationArea::query()->create(['area' => 'geschaeftsstelle', 'show_header' => false, 'show_lists' => true]);
    NavigationControl::reset();

    expect(PortalNavigation::visibleAreas($user))->not->toContain(PortalAreas::Geschaeftsstelle)
        ->and(PortalNavigation::visibleAreas($user, 'lists'))->toContain(PortalAreas::Geschaeftsstelle);
});

it('adds own links with sub pages and puts them where the layout says', function () {
    $user = ncUser(RoleKey::BoardMember);
    $admin = ncUser(RoleKey::Administration);

    $layout = [
        ['ref' => 'new1', 'kind' => 'custom', 'label' => 'Handbuch', 'url' => 'https://example.org/handbuch', 'visible' => true, 'parent' => null],
        ['ref' => 'new2', 'kind' => 'custom', 'label' => 'Kapitel 1', 'url' => '/impressum', 'visible' => true, 'parent' => 'new1'],
    ];

    $this->actingAs($admin)->put(route('navigation.header.update', 'geschaeftsstelle'), ['layout' => json_encode($layout)])->assertRedirect(route('navigation.header', 'geschaeftsstelle'));

    $pages = collect(PortalNavigation::pages(PortalAreas::Geschaeftsstelle, $user));
    expect($pages->pluck('label')->all())->toContain('Handbuch', 'Personen & Zugang')
        ->and($pages->pluck('label')->search('Handbuch'))->toBeLessThan($pages->pluck('label')->search('Personen & Zugang'))
        ->and($pages->firstWhere('label', 'Handbuch')['children'][0]['href'])->toBe('/impressum');

    $this->actingAs($admin)->get(route('navigation.header', 'geschaeftsstelle'))->assertOk()->assertSee('Handbuch');
});

it('rejects an invalid header layout', function () {
    $admin = ncUser(RoleKey::Administration);
    $bad = [['ref' => 'x', 'kind' => 'custom', 'label' => 'Ohne Adresse', 'url' => '', 'visible' => true, 'parent' => null]];

    $this->actingAs($admin)->put(route('navigation.header.update', 'geschaeftsstelle'), ['layout' => json_encode($bad)])->assertSessionHasErrors('layout');
    $this->actingAs($admin)->put(route('navigation.header.update', 'geschaeftsstelle'), ['layout' => 'kein json'])->assertSessionHasErrors('layout');
});

it('lists the web control in the area Admins only for administration', function () {
    $labels = fn (RoleKey $role): array => collect(PortalNavigation::pages(PortalAreas::Admins, ncUser($role)))->pluck('label')->all();

    expect($labels(RoleKey::Administration))->toContain('Web-Control')->and($labels(RoleKey::BoardMember))->not->toContain('Web-Control');
});

it('lets only administration use the web control, orders and renames areas', function () {
    $this->actingAs(ncUser(RoleKey::Member))->get(route('navigation.edit'))->assertForbidden();

    $admin = ncUser(RoleKey::Administration);
    $this->actingAs($admin)->get(route('navigation.edit'))->assertOk()->assertSee('Geschäftsstelle');
    $this->actingAs($admin)->get(route('navigation.headers'))->assertOk();
    $this->actingAs($admin)->get(route('navigation.header', 'koordination'))->assertOk();
    $this->actingAs($admin)->get(route('navigation.area', 'koordination'))->assertOk();

    $this->actingAs($admin)->post(route('navigation.area.toggle', 'koordination'), ['field' => 'show_header'])->assertRedirect(route('navigation.edit'));
    expect(NavigationArea::query()->where('area', 'koordination')->value('show_header'))->toBeFalse();

    $this->actingAs($admin)->put(route('navigation.area.update', 'teamende'), ['label' => 'Team', 'show_header' => '1', 'show_lists' => '1', 'roles' => ['member']])->assertRedirect(route('navigation.edit'));
    NavigationControl::reset();
    expect(NavigationControl::areaLabel(PortalAreas::Teamende))->toBe('Team');

    $this->actingAs($admin)->post(route('navigation.area.move', 'teamende'), ['direction' => 'up'])->assertRedirect(route('navigation.edit'));
    expect(NavigationArea::query()->where('area', 'teamende')->value('sort'))->not->toBeNull();
});
