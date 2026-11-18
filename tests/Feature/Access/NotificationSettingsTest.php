<?php

use App\Modules\Access\Models\User;
use App\Modules\Access\Services\NotificationPreferences;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\Membership\Models\Membership;
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
    $this->actingAs(nsUser(RoleKey::Team))->get(route('account.notifications'))->assertOk()
        ->assertSee('Mitteilungen an Teamende')->assertSee('Erinnerungen an Ihre Einarbeitung')->assertDontSee('Neue Mitgliedsanträge')->assertSee('Immer aktiv');

    $this->actingAs(nsUser(RoleKey::BoardMember))->get(route('account.notifications'))->assertOk()->assertSee('Neue Mitgliedsanträge')->assertSee('Neue Zugangsanträge');

    $this->actingAs(nsUser())->get(route('account.notifications'))->assertOk()->assertSee('keine weiteren Benachrichtigungen');
});

it('has every notification on by default and saves the choice per entry', function () {
    $user = nsUser(RoleKey::Team);
    $preferences = app(NotificationPreferences::class);

    expect($preferences->allows($user, 'team_mailings'))->toBeTrue()->and($preferences->allows($user, 'onboarding_reminders'))->toBeTrue();

    $this->actingAs($user)->put(route('account.notifications.update'), ['notifications' => ['onboarding_reminders']])->assertRedirect(route('account.notifications'))->assertSessionHas('success');

    expect($preferences->allows($user, 'onboarding_reminders'))->toBeTrue()->and($preferences->allows($user, 'team_mailings'))->toBeFalse();

    $this->put(route('account.notifications.update'), [])->assertSessionHasNoErrors();
    expect($preferences->allows($user, 'onboarding_reminders'))->toBeFalse();

    $this->get(route('account.notifications'))->assertOk();
});

it('ignores keys that do not belong to the account and unknown keys', function () {
    $user = nsUser(RoleKey::Team);
    $this->actingAs($user)->put(route('account.notifications.update'), ['notifications' => ['new_applications', 'erfunden']]);

    expect(DB::table('notification_preferences')->where('user_id', $user->id)->pluck('key')->all())->toContain('team_mailings')->toContain('onboarding_reminders')->not->toContain('new_applications')->not->toContain('erfunden')
        ->and(app(NotificationPreferences::class)->allows($user, 'erfunden'))->toBeTrue();
});

it('filters recipients by their choice and keeps mandatory mails out of the catalog', function () {
    $wants = nsUser(RoleKey::Team);
    $declines = nsUser(RoleKey::Team);
    DB::table('notification_preferences')->insert(['user_id' => $declines->id, 'key' => 'team_mailings', 'enabled' => false, 'created_at' => now(), 'updated_at' => now()]);

    $kept = app(NotificationPreferences::class)->filter([$wants, $declines], 'team_mailings');

    expect(array_map(fn (User $user): int => $user->id, $kept))->toBe([$wants->id])
        ->and(array_keys(NotificationPreferences::catalog()))->not->toContain('two_factor')->not->toContain('password_reset');
});

it('logs a change of the settings without any content', function () {
    $user = nsUser(RoleKey::Team);
    $this->actingAs($user)->put(route('account.notifications.update'), ['notifications' => []]);

    $entry = AuditEntry::query()->where('event', 'account.notifications_changed')->firstOrFail();
    expect(json_encode($entry->context))->not->toContain('team_mailings');
});

it('offers the appearance page', function () {
    $this->actingAs(nsUser())->get(route('account.appearance'))->assertOk()->assertSee('Farbschema')->assertSee('data-vdbs-theme-set="dark"', false);
});

it('keeps the membership out of the account and shows it in the area Mitglieder', function () {
    $person = Person::factory()->create();
    $member = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);
    RoleAssignment::query()->create(['user_id' => $member->id, 'role_id' => Role::query()->where('key', 'member')->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    Membership::factory()->create(['person_id' => $person->id, 'starts_on' => '2023-05-01']);

    $this->actingAs($member)->get(route('account.roles'))->assertOk()->assertDontSee('01.05.2023')->assertDontSee('Mitglied seit');
    $this->get(route('member.membership'))->assertOk()->assertSee('Mitglied seit')->assertSee('01.05.2023')->assertSee('Aktiv')->assertSee('Vorstand kontaktieren');

    $this->actingAs(nsUser())->get(route('member.membership'))->assertForbidden();
});
