<?php

use App\Modules\Access\Enums\TwoFactorMethodType;
use App\Modules\Access\Models\User;
use App\Modules\Access\Services\TwoFactorPolicy;
use App\Modules\Access\Services\Totp;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    config(['portal.two_factor.required' => true]);
});

function trUser(?RoleKey $role = null, bool $withTotp = false, array $assignment = []): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    if ($role !== null) {
        RoleAssignment::query()->create($assignment + [
            'user_id' => $user->id,
            'role_id' => Role::query()->where('key', $role->value)->value('id'),
            'source' => RoleAssignmentSource::Manual,
            'starts_at' => now()->subMinute(),
        ]);
    }

    if ($withTotp) {
        $user->twoFactorMethods()->create(['type' => TwoFactorMethodType::Totp, 'secret' => Totp::generateSecret(), 'confirmed_at' => now()]);
    }

    return $user;
}

// ---------- Wer muss ----------

it('requires two-factor for administration, staff and board but not for others', function () {
    $policy = app(TwoFactorPolicy::class);

    foreach ([RoleKey::Administration, RoleKey::AdministrationStaff, RoleKey::BoardMember] as $role) {
        expect($policy->isRequired(trUser($role)))->toBeTrue()
            ->and($policy->mustSetUp(trUser($role)))->toBeTrue()
            ->and($policy->mustSetUp(trUser($role, true)))->toBeFalse();
    }

    foreach ([null, RoleKey::Member, RoleKey::Team, RoleKey::Coordination, RoleKey::EducationCoordination] as $role) {
        expect($policy->isRequired(trUser($role)))->toBeFalse();
    }
});

it('ignores ended roles and can be switched off', function () {
    expect(app(TwoFactorPolicy::class)->isRequired(trUser(RoleKey::Administration, false, ['ends_at' => now()->subMinute()])))->toBeFalse();

    config(['portal.two_factor.required' => false]);

    expect(app(TwoFactorPolicy::class)->isRequired(trUser(RoleKey::Administration)))->toBeFalse();
});

// ---------- Erzwingen ----------

it('sends users with a duty and without a method to the security page', function () {
    $staff = trUser(RoleKey::AdministrationStaff);

    $this->actingAs($staff)->get(route('admin.users.index'))
        ->assertRedirect(route('account.security.index'))
        ->assertSessionHas('warning');

    $this->get(route('account.profile'))->assertRedirect(route('account.security.index'));
    $this->get(route('account.password'))->assertRedirect(route('account.security.index'));
});

it('keeps the security pages, the logout and the public pages reachable', function () {
    $staff = trUser(RoleKey::Administration);
    $this->actingAs($staff);

    $this->get(route('account.security.index'))->assertOk()->assertSee('Zwei-Faktor ist für Ihre Rolle Pflicht');
    $this->get(route('account.security.totp'))->assertOk();
    $this->get(route('account.security.email'))->assertOk();
    $this->get(route('home'))->assertOk();
    $this->get(route('portal.about'))->assertOk();

    $this->post(route('logout'))->assertRedirect(route('login'));
});

it('lets users with a method work normally', function () {
    $this->actingAs(trUser(RoleKey::AdministrationStaff, true))->get(route('admin.users.index'))->assertOk();
});

it('does not touch accounts without a duty', function () {
    $this->actingAs(trUser(RoleKey::Member))->get(route('account.profile'))->assertOk();
    $this->actingAs(trUser())->get(route('account.profile'))->assertOk();
});

it('answers json requests with 403 instead of a redirect', function () {
    $this->actingAs(trUser(RoleKey::Administration))->getJson(route('design-library.placeholders'))->assertForbidden();
});

it('lifts the duty as soon as a method is active', function () {
    $staff = trUser(RoleKey::AdministrationStaff);
    $this->actingAs($staff)->get(route('admin.users.index'))->assertRedirect(route('account.security.index'));

    $staff->twoFactorMethods()->create(['type' => TwoFactorMethodType::Totp, 'secret' => Totp::generateSecret(), 'confirmed_at' => now()]);

    $this->get(route('admin.users.index'))->assertOk();
});

it('forces the setup again after the administration reset the two-factor methods', function () {
    $admin = trUser(RoleKey::Administration, true);
    $target = trUser(RoleKey::AdministrationStaff, true);

    $this->actingAs($admin)->post(route('admin.users.two-factor.reset', $target))->assertRedirect();

    $this->actingAs($target->fresh())->get(route('admin.users.index'))->assertRedirect(route('account.security.index'));
});

// ---------- Letzte Methode ----------

it('does not let a user with a duty remove the last method', function () {
    $staff = trUser(RoleKey::AdministrationStaff, true);

    $this->actingAs($staff)->delete(route('account.security.destroy', 'totp'), ['password' => 'password'])
        ->assertRedirect(route('account.security.index'))
        ->assertSessionHas('warning');

    expect(app(\App\Modules\Access\Services\TwoFactorManager::class)->has($staff, TwoFactorMethodType::Totp))->toBeTrue();
});

it('lets a user with a duty remove a method when another one remains', function () {
    $staff = trUser(RoleKey::AdministrationStaff, true);
    $staff->twoFactorMethods()->create(['type' => TwoFactorMethodType::Email, 'confirmed_at' => now()]);

    $this->actingAs($staff)->delete(route('account.security.destroy', 'totp'), ['password' => 'password'])->assertSessionHas('success');

    expect(app(\App\Modules\Access\Services\TwoFactorManager::class)->has($staff, TwoFactorMethodType::Totp))->toBeFalse();
});

it('lets members remove their last method as before', function () {
    $member = trUser(RoleKey::Member, true);

    $this->actingAs($member)->delete(route('account.security.destroy', 'totp'), ['password' => 'password'])->assertSessionHas('success');

    expect(app(\App\Modules\Access\Services\TwoFactorManager::class)->isEnabled($member))->toBeFalse();
});

it('tells users with a duty why the last method cannot be removed', function () {
    $this->actingAs(trUser(RoleKey::AdministrationStaff, true))->get(route('account.security.index'))
        ->assertOk()
        ->assertSee('Die letzte Methode kann deshalb nicht entfernt werden');
});
