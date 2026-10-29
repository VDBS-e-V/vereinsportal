<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\Capability;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\AccessGovernance\Support\Capabilities;
use App\Modules\People\Models\Person;
use App\Support\PortalAreas;
use App\Support\PortalNavigation;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

function pnUser(?RoleKey $role = null): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    if ($role !== null) {
        RoleAssignment::query()->create([
            'user_id' => $user->id,
            'role_id' => Role::query()->where('key', $role->value)->value('id'),
            'source' => RoleAssignmentSource::Manual,
            'starts_at' => now()->subMinute(),
        ]);
    }

    return $user;
}

function pnHeader(string $html): string
{
    preg_match('/<header class="vdbs-header".*?<\/header>/s', $html, $matches);

    return $matches[0] ?? '';
}

function pnAreaLabels(User $user): array
{
    return array_map(fn (PortalAreas $area): string => $area->label(), PortalNavigation::visibleAreas($user));
}

// ---------- Katalog ----------

it('lists every page of the catalog under exactly one area with an existing route and a known capability', function () {
    $capabilities = array_map(fn (Capability $case): string => $case->value, Capability::cases());
    $seen = [];

    foreach (PortalNavigation::catalog() as $key => $definition) {
        foreach ($definition['pages'] as $page) {
            expect(Route::has($page['route']))->toBeTrue()
                ->and(PortalNavigation::areaForRoute($page['route']))->toBe(PortalAreas::from($key));

            if (isset($page['can'])) {
                expect($capabilities)->toContain($page['can']);
            }

            expect($seen)->not->toContain($page['route']);
            $seen[] = $page['route'];
        }
    }
});

it('assigns every page behind the login to an area', function () {
    $exempt = ['passport.', 'oauth.', 'ignition.', 'debugbar.', 'livewire.', 'volt.', 'storage.', 'two-factor.'];
    $unassigned = [];

    foreach (Route::getRoutes() as $route) {
        $name = $route->getName();

        if ($name === null || ! in_array('GET', $route->methods(), true) || ! in_array('auth', $route->gatherMiddleware(), true) || Str::startsWith($name, $exempt)) {
            continue;
        }

        if (PortalNavigation::areaForRoute($name) === null) {
            $unassigned[] = $name;
        }
    }

    // Schlägt dies fehl: die genannten Seiten in PortalNavigation::catalog() einem Bereich zuweisen.
    expect($unassigned)->toBe([]);
});

it('makes every page reachable for every role that holds its capability', function () {
    foreach (PortalNavigation::catalog() as $key => $definition) {
        $area = PortalAreas::from($key);

        foreach ($definition['pages'] as $page) {
            if (! isset($page['can'])) {
                continue;
            }

            foreach (RoleKey::cases() as $role) {
                $user = pnUser($role);

                if (Capabilities::has($user, Capability::from($page['can']))) {
                    expect(PortalNavigation::visibleAreas($user))->toContain($area);
                }
            }
        }
    }
});

it('knows no area for pages outside the portal areas', function () {
    expect(PortalNavigation::areaForRoute('login'))->toBeNull()
        ->and(PortalNavigation::areaForRoute(null))->toBeNull()
        ->and(PortalNavigation::areaForRoute('home'))->toBe(PortalAreas::Start)
        ->and(PortalNavigation::areaForRoute('account.security.totp.store'))->toBe(PortalAreas::Konto);
});

// ---------- Sichtbarkeit ----------

it('shows areas by role and only when the user may see a page in them', function () {
    expect(pnAreaLabels(pnUser()))->toBe([])
        ->and(pnAreaLabels(pnUser(RoleKey::Member)))->toBe([])
        ->and(pnAreaLabels(pnUser(RoleKey::AdministrationStaff)))->toBe(['Geschäftsstelle'])
        ->and(pnAreaLabels(pnUser(RoleKey::Administration)))->toBe(['Geschäftsstelle', 'Admins'])
        ->and(pnAreaLabels(pnUser(RoleKey::BoardMember)))->toBe(['Geschäftsstelle', 'Admins']);
});

it('ignores roles that have ended', function () {
    $user = pnUser();
    RoleAssignment::query()->create([
        'user_id' => $user->id,
        'role_id' => Role::query()->where('key', 'administration_staff')->value('id'),
        'source' => RoleAssignmentSource::Manual,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->subMinute(),
    ]);

    expect(PortalNavigation::visibleAreas($user))->toBe([]);
});

it('lists the pages of an area by capability and marks the current one', function () {
    $staff = pnUser(RoleKey::AdministrationStaff);
    $admin = pnUser(RoleKey::Administration);

    expect(array_column(PortalNavigation::pages(PortalAreas::Geschaeftsstelle, $staff), 'label'))->toBe(['Nutzer', 'Personen'])
        ->and(array_column(PortalNavigation::pages(PortalAreas::Admins, $staff), 'label'))->toBe([])
        ->and(array_column(PortalNavigation::pages(PortalAreas::Admins, $admin), 'label'))->toBe(['Protokoll', 'Design-Bibliothek'])
        ->and(PortalNavigation::pages(PortalAreas::Geschaeftsstelle, $staff, 'admin.people.show')[1]['current'])->toBeTrue()
        ->and(PortalNavigation::pages(PortalAreas::Geschaeftsstelle, $staff, 'admin.people.show')[0]['current'])->toBeFalse()
        ->and(PortalNavigation::home(PortalAreas::Geschaeftsstelle, $staff))->toBe(route('admin.users.index'));
});

// ---------- Kopfbereich ----------

it('shows the areas in row one and the pages of the current area in row two', function () {
    $staff = pnUser(RoleKey::AdministrationStaff);

    $html = $this->actingAs($staff)->get(route('admin.users.index'))->assertOk()->getContent();
    $html = pnHeader($html);

    expect($html)
        ->toMatch('/aria-current="true"\s*>Geschäftsstelle</')
        ->toContain('aria-label="Seiten im Bereich Geschäftsstelle"')
        ->toMatch('/aria-current="page"\s*>Nutzer</')
        ->toContain('>Personen<')
        ->not->toContain('>Admins<')
        ->not->toContain('>Protokoll<');
});

it('names the current area in the header and highlights the area of the page', function () {
    $admin = pnUser(RoleKey::Administration);

    $html = $this->actingAs($admin)->get(route('design-library.index'))->assertOk()->getContent();
    $html = pnHeader($html);

    expect($html)
        ->toMatch('/aria-current="true"\s*>Admins</')
        ->toContain('aria-label="Seiten im Bereich Admins"')
        ->toMatch('/vdbs-header__title">\s*Admins\s*</');
});

it('shows the account pages in row two without highlighting an area', function () {
    $user = pnUser(RoleKey::Administration);

    $html = $this->actingAs($user)->get(route('account.security.index'))->assertOk()->getContent();
    $html = pnHeader($html);

    expect($html)
        ->toContain('aria-label="Seiten im Bereich Konto"')
        ->toMatch('/aria-current="page"\s*>Sicherheit</')
        ->not->toMatch('/aria-current="true"/');

    foreach (['Profil', 'E-Mail', 'Passwort', 'Sitzungen'] as $label) {
        expect($html)->toContain('>'.$label.'<');
    }
});

it('shows no page links on the start page and no areas to members', function () {
    $html = $this->actingAs(pnUser(RoleKey::Member))->get(route('home'))->assertOk()->getContent();
    $html = pnHeader($html);

    expect($html)->not->toContain('Seiten im Bereich')
        ->and($html)->not->toContain('>Geschäftsstelle<')
        ->and($html)->not->toContain('>Admins<');
});

it('keeps the pages protected by their capability even when the area is visible', function () {
    $staff = pnUser(RoleKey::AdministrationStaff);

    $this->actingAs($staff)->get(route('admin.audit.index'))->assertForbidden();
});
