<?php

use App\Modules\Access\Models\User;
use App\Modules\Access\Services\TwoFactorPolicy;
use App\Modules\AccessGovernance\Enums\Capability;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\AccessGovernance\Support\Capabilities;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\People\Models\Person;
use App\Support\PortalAreas;
use App\Support\PortalNavigation;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

function erUser(?RoleKey $role = null): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

it('knows the three new roles with their names', function () {
    expect(RoleKey::Employee->label())->toBe('Mitarbeitende')
        ->and(RoleKey::ManagingDirector->label())->toBe('Geschäftsführung')
        ->and(RoleKey::Treasury->label())->toBe('Kassenführung')
        ->and(Role::query()->whereIn('key', ['employee', 'managing_director', 'treasury'])->where('is_system', true)->count())->toBe(3);
});

it('gives the new roles exactly their rights', function () {
    $employee = erUser(RoleKey::Employee);
    $director = erUser(RoleKey::ManagingDirector);
    $treasury = erUser(RoleKey::Treasury);

    expect(Capabilities::has($employee, Capability::FormsSubmit))->toBeTrue()
        ->and(Capabilities::has($employee, Capability::EmployeeArea))->toBeTrue()
        ->and(Capabilities::has($employee, Capability::FormsApprove))->toBeFalse()
        ->and(Capabilities::has($employee, Capability::FormsManage))->toBeFalse()
        ->and(Capabilities::has($employee, Capability::UsersView))->toBeFalse()
        ->and(Capabilities::has($director, Capability::FormsApprove))->toBeTrue()
        ->and(Capabilities::has($director, Capability::FormsSubmit))->toBeTrue()
        ->and(Capabilities::has($director, Capability::FormsManage))->toBeFalse()
        ->and(Capabilities::has($treasury, Capability::FormsManage))->toBeTrue()
        ->and(Capabilities::has($treasury, Capability::FormsApprove))->toBeFalse()
        ->and(Capabilities::has(erUser(RoleKey::AdministrationStaff), Capability::FormsApprove))->toBeFalse();
});

it('lets only the board name or remove the managing director and the treasury', function () {
    $board = erUser(RoleKey::BoardMember);
    $staff = erUser(RoleKey::AdministrationStaff);
    $admin = erUser(RoleKey::Administration);

    foreach ([RoleKey::ManagingDirector, RoleKey::Treasury] as $role) {
        expect(Capabilities::canAssign($board, $role))->toBeTrue()
            ->and(Capabilities::canAssign($staff, $role))->toBeFalse()
            ->and(Capabilities::canAssign($admin, $role))->toBeFalse();
    }

    expect(Capabilities::canAssign($staff, RoleKey::Employee))->toBeTrue()
        ->and(Capabilities::canAssign($board, RoleKey::Employee))->toBeTrue();
});

it('enforces the assignment rule on the pages for naming and ending the role', function () {
    $target = erUser(null);
    $roleId = Role::query()->where('key', 'managing_director')->value('id');

    $this->actingAs(erUser(RoleKey::AdministrationStaff))->post(route('admin.users.roles.store', $target), ['role_id' => $roleId])->assertForbidden();
    expect($target->activeRoleAssignments()->count())->toBe(0);

    $this->actingAs(erUser(RoleKey::BoardMember))->post(route('admin.users.roles.store', $target), ['role_id' => $roleId])->assertRedirect();
    $assignment = $target->activeRoleAssignments()->firstOrFail();

    $this->actingAs(erUser(RoleKey::AdministrationStaff))->delete(route('admin.users.roles.destroy', [$target, $assignment]))->assertForbidden();
    $this->actingAs(erUser(RoleKey::BoardMember))->delete(route('admin.users.roles.destroy', [$target, $assignment]))->assertRedirect();
    expect($target->activeRoleAssignments()->count())->toBe(0);
});

it('lets the office name employees and shows only allowed roles in the form', function () {
    $target = erUser(null);
    $staff = erUser(RoleKey::AdministrationStaff);

    $this->actingAs($staff)->post(route('admin.users.roles.store', $target), ['role_id' => Role::query()->where('key', 'employee')->value('id'), 'comment' => 'Schulbibliothek'])->assertRedirect();
    expect($target->activeRoleAssignments()->count())->toBe(1);

    $page = $this->actingAs($staff)->get(route('admin.users.show', $target))->assertOk()->getContent();
    expect($page)->not->toContain('Geschaeftsfuehrung')->not->toContain('Kassenfuehrung');
});

it('keeps the place of work out of the log', function () {
    $target = erUser(null);

    $this->actingAs(erUser(RoleKey::AdministrationStaff))->post(route('admin.users.roles.store', $target), ['role_id' => Role::query()->where('key', 'employee')->value('id'), 'comment' => 'Schulbibliothek Nord'])->assertRedirect();

    $entry = AuditEntry::query()->where('event', 'role.assigned')->latest('id')->firstOrFail();
    expect(json_encode($entry->context))->toContain('Mitarbeitende')->not->toContain('Schulbibliothek');
    expect(RoleAssignment::query()->where('user_id', $target->id)->value('comment'))->toBe('Schulbibliothek Nord');
});

it('requires two-factor for the managing director and the treasury but not for employees', function () {
    config(['portal.two_factor.required' => true]);
    $policy = app(TwoFactorPolicy::class);

    expect($policy->isRequired(erUser(RoleKey::ManagingDirector)))->toBeTrue()
        ->and($policy->isRequired(erUser(RoleKey::Treasury)))->toBeTrue()
        ->and($policy->isRequired(erUser(RoleKey::Employee)))->toBeFalse();

    $this->actingAs(erUser(RoleKey::ManagingDirector))->get(route('employee.home'))->assertRedirect(route('account.security.index'));
});

it('shows the area Mitarbeitende only to the new roles', function () {
    $labels = fn (User $user): array => array_map(fn (PortalAreas $area): string => $area->label(), PortalNavigation::visibleAreas($user));

    expect($labels(erUser(RoleKey::Employee)))->toContain('Mitarbeitende')
        ->and($labels(erUser(RoleKey::ManagingDirector)))->toContain('Mitarbeitende')
        ->and($labels(erUser(RoleKey::Treasury)))->toContain('Mitarbeitende')
        ->and($labels(erUser(RoleKey::BoardMember)))->not->toContain('Mitarbeitende')
        ->and($labels(erUser(RoleKey::Team)))->not->toContain('Mitarbeitende')
        ->and($labels(erUser(RoleKey::AdministrationStaff)))->not->toContain('Mitarbeitende');
});

it('opens the employee page for the new roles and keeps others out', function () {
    config(['portal.systems' => [['label' => 'Nextcloud', 'url' => 'https://cloud.example.org'], ['label' => 'Moodle', 'url' => null], ['label' => 'Umfragen', 'url' => null]]]);

    $this->actingAs(erUser(RoleKey::Employee))->get(route('employee.home'))->assertOk()->assertSee('Bereich Mitarbeitende')->assertSee('https://cloud.example.org')->assertSee('Mitarbeitende');
    $this->actingAs(erUser(RoleKey::Treasury))->get(route('employee.home'))->assertOk();
    $this->actingAs(erUser(RoleKey::BoardMember))->get(route('employee.home'))->assertForbidden();
    $this->actingAs(erUser(RoleKey::Team))->get(route('employee.home'))->assertForbidden();
});

it('lets employees and the managing director log in to other systems by default', function () {
    expect(\App\Modules\Sso\Services\OAuthClients::defaultRoles())->toContain('employee', 'managing_director')
        ->and(\App\Modules\Sso\Services\OidcClaims::GROUPS['employee'])->toBe('vdbs-mitarbeitende')
        ->and(\App\Modules\Sso\Services\OidcClaims::GROUPS['managing_director'])->toBe('vdbs-geschaeftsfuehrung');
});

it('creates test accounts for every role locally and only there', function () {
    $this->artisan('portal:demo-users')->assertSuccessful();

    foreach (RoleKey::cases() as $role) {
        if ($role === RoleKey::Guest) {
            continue;
        }

        $user = User::query()->where('email', str_replace('_', '-', $role->value).'@demo.test')->first();
        expect($user)->not->toBeNull()->and(Capabilities::activeRoleKeys($user))->toBe([$role->value]);
    }

    $this->artisan('portal:demo-users')->assertSuccessful();
    expect(User::query()->where('email', 'like', '%@demo.test')->count())->toBe(11);

    $this->app['env'] = 'production';
    $this->artisan('portal:demo-users')->assertFailed();
});
