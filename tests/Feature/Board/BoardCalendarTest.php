<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

function bcUser(RoleKey $role): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);
    RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);

    return $user;
}

it('shows the board an empty calendar page and saves the shared link', function () {
    $this->actingAs(bcUser(RoleKey::BoardMember))->get(route('board.calendar.show'))->assertOk()->assertSee('Es ist noch kein Kalenderlink hinterlegt');

    $this->put(route('board.calendar.update'), ['calendar_url' => 'https://cloud.example.org/calendar/vorstand', 'feed_url' => 'https://cloud.example.org/calendar/vorstand.ics', 'notes' => 'Jeden ersten Dienstag, 19 Uhr'])
        ->assertRedirect(route('board.calendar.show'));

    $this->get(route('board.calendar.show'))->assertOk()->assertSee('https://cloud.example.org/calendar/vorstand', false)->assertSee('webcal://cloud.example.org/calendar/vorstand.ics', false)->assertSee('Jeden ersten Dienstag, 19 Uhr');

    expect(AuditEntry::query()->where('event', 'board.calendar_changed')->count())->toBe(1);
});

it('accepts only https links', function () {
    $this->actingAs(bcUser(RoleKey::BoardMember));

    foreach (['javascript:alert(1)', 'http://example.org/kalender', 'kein link'] as $bad) {
        $this->put(route('board.calendar.update'), ['calendar_url' => $bad])->assertSessionHasErrors('calendar_url');
    }

    $this->put(route('board.calendar.update'), ['calendar_url' => '', 'feed_url' => '', 'notes' => ''])->assertSessionHasNoErrors();
});

it('keeps the calendar from everyone but the board', function () {
    $this->get(route('board.calendar.show'))->assertRedirect(route('login'));

    foreach ([RoleKey::AdministrationStaff, RoleKey::Coordination, RoleKey::Team, RoleKey::Member, RoleKey::Administration] as $role) {
        $this->actingAs(bcUser($role))->get(route('board.calendar.show'))->assertForbidden();
        $this->put(route('board.calendar.update'), ['calendar_url' => 'https://cloud.example.org/x'])->assertForbidden();
    }
});

it('lists the calendar in the board navigation', function () {
    $this->actingAs(bcUser(RoleKey::BoardMember))->get(route('board.overview'))->assertOk()->assertSee(route('board.calendar.show'), false);
});
