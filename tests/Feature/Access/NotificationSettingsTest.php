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
    $this->actingAs(nsUser(RoleKey::Coordination))->get(route('account.notifications'))->assertOk()
        ->assertSee('Nachrichten von Schulen')->assertSee('Stichtag für Interessenbekundungen abgelaufen')->assertDontSee('Neue Mitgliedsanträge')->assertSee('Immer aktiv');

    $this->actingAs(nsUser(RoleKey::BoardMember))->get(route('account.notifications'))->assertOk()->assertSee('Neue Mitgliedsanträge')->assertSee('Neue Zugangsanträge');

    $this->actingAs(nsUser())->get(route('account.notifications'))->assertOk()->assertSee('keine weiteren Benachrichtigungen');
});

it('has every notification on by default and saves the choice per entry', function () {
    $user = nsUser(RoleKey::Coordination);
    $preferences = app(NotificationPreferences::class);

    expect($preferences->allows($user, 'school_requests'))->toBeTrue()->and($preferences->allows($user, 'seminar_deadline'))->toBeTrue();

    $this->actingAs($user)->put(route('account.notifications.update'), ['notifications' => ['seminar_deadline']])->assertRedirect(route('account.notifications'))->assertSessionHas('success');

    expect($preferences->allows($user, 'seminar_deadline'))->toBeTrue()->and($preferences->allows($user, 'school_requests'))->toBeFalse();

    $this->put(route('account.notifications.update'), [])->assertSessionHasNoErrors();
    expect($preferences->allows($user, 'seminar_deadline'))->toBeFalse();

    $this->get(route('account.notifications'))->assertOk();
});

it('ignores keys that do not belong to the account and unknown keys', function () {
    $user = nsUser(RoleKey::Coordination);
    $this->actingAs($user)->put(route('account.notifications.update'), ['notifications' => ['new_applications', 'erfunden']]);

    expect(\Illuminate\Support\Facades\DB::table('notification_preferences')->where('user_id', $user->id)->pluck('key')->all())->toContain('school_requests')->toContain('seminar_deadline')->not->toContain('new_applications')->not->toContain('erfunden')
        ->and(app(NotificationPreferences::class)->allows($user, 'erfunden'))->toBeTrue();
});

it('filters recipients by their choice and keeps mandatory mails out of the catalog', function () {
    $wants = nsUser(RoleKey::Coordination);
    $declines = nsUser(RoleKey::Coordination);
    \Illuminate\Support\Facades\DB::table('notification_preferences')->insert(['user_id' => $declines->id, 'key' => 'school_requests', 'enabled' => false, 'created_at' => now(), 'updated_at' => now()]);

    $kept = app(NotificationPreferences::class)->filter([$wants, $declines], 'school_requests');

    expect(array_map(fn (User $user): int => $user->id, $kept))->toBe([$wants->id])
        ->and(array_keys(NotificationPreferences::catalog()))->not->toContain('two_factor')->not->toContain('password_reset');
});

it('logs a change of the settings without any content', function () {
    $user = nsUser(RoleKey::Coordination);
    $this->actingAs($user)->put(route('account.notifications.update'), ['notifications' => []]);

    $entry = AuditEntry::query()->where('event', 'account.notifications_changed')->firstOrFail();
    expect(json_encode($entry->context))->not->toContain('school_requests');
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
