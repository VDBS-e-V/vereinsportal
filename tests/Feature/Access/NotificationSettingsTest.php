<?php

use App\Modules\Access\Models\User;
use App\Modules\Access\Services\NotificationPreferences;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

function nsUser(?RoleKey $role = null): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

it('offers only the notifications that fit the roles of the account', function () {
    $this->actingAs(nsUser(RoleKey::AdministrationStaff))->get(route('account.notifications'))->assertOk()->assertSee('Neue Zugangsanträge')->assertSee('Immer aktiv');

    $this->actingAs(nsUser())->get(route('account.notifications'))->assertOk()->assertDontSee('Neue Zugangsanträge')->assertSee('keine weiteren Benachrichtigungen');
});

it('has every notification on by default and saves the choice per entry', function () {
    $user = nsUser(RoleKey::AdministrationStaff);
    $preferences = app(NotificationPreferences::class);

    expect($preferences->allows($user, 'new_access_requests'))->toBeTrue();

    $this->actingAs($user)->put(route('account.notifications.update'), ['notifications' => ['new_access_requests']])->assertRedirect(route('account.notifications'))->assertSessionHas('success');

    expect($preferences->allows($user, 'new_access_requests'))->toBeTrue();

    $this->put(route('account.notifications.update'), [])->assertSessionHasNoErrors();
    expect($preferences->allows($user, 'new_access_requests'))->toBeFalse();

    $this->get(route('account.notifications'))->assertOk();
});

it('ignores keys that do not belong to the account and unknown keys', function () {
    $user = nsUser();
    $this->actingAs($user)->put(route('account.notifications.update'), ['notifications' => ['new_access_requests', 'erfunden']]);

    expect(DB::table('notification_preferences')->where('user_id', $user->id)->pluck('key')->all())->not->toContain('new_access_requests')->not->toContain('erfunden')
        ->and(app(NotificationPreferences::class)->allows($user, 'erfunden'))->toBeTrue();
});

it('filters recipients by their choice and keeps mandatory mails out of the catalog', function () {
    $wants = nsUser(RoleKey::AdministrationStaff);
    $declines = nsUser(RoleKey::AdministrationStaff);
    DB::table('notification_preferences')->insert(['user_id' => $declines->id, 'key' => 'new_access_requests', 'enabled' => false, 'created_at' => now(), 'updated_at' => now()]);

    $kept = app(NotificationPreferences::class)->filter([$wants, $declines], 'new_access_requests');

    expect(array_map(fn (User $user): int => $user->id, $kept))->toBe([$wants->id])
        ->and(array_keys(NotificationPreferences::catalog()))->not->toContain('two_factor')->not->toContain('password_reset');
});

it('logs a change of the settings without any content', function () {
    $user = nsUser(RoleKey::AdministrationStaff);
    $this->actingAs($user)->put(route('account.notifications.update'), ['notifications' => []]);

    $entry = AuditEntry::query()->where('event', 'account.notifications_changed')->firstOrFail();
    expect(json_encode($entry->context))->not->toContain('new_access_requests');
});

it('offers the appearance page', function () {
    $this->actingAs(nsUser())->get(route('account.appearance'))->assertOk()->assertSee('Farbschema')->assertSee('data-vdbs-theme-set="dark"', false);
});
