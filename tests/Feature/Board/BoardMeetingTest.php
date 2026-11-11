<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\Board\Models\BoardMeeting;
use App\Modules\Board\Services\BoardCalendar;
use App\Modules\Board\Support\IcsFeed;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

function bmUser(RoleKey $role, array $person = []): User
{
    $p = Person::factory()->create($person);
    $user = User::factory()->create(['person_id' => $p->id, 'email' => $p->email]);
    RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);

    return $user;
}

function bmData(array $override = []): array
{
    return array_merge(['title' => 'Vorstandssitzung Dezember', 'date' => now()->addDays(10)->format('Y-m-d'), 'start_time' => '19:00', 'end_time' => '21:00', 'location' => 'Geschäftsstelle', 'status' => 'planned'], $override);
}

it('creates a meeting with agenda, attendance and minutes and shows it', function () {
    $board = bmUser(RoleKey::BoardMember, ['first_name' => 'Berta', 'last_name' => 'Vorstand']);
    $other = bmUser(RoleKey::BoardMember, ['first_name' => 'Carl', 'last_name' => 'Beisitz']);
    $this->actingAs($board)->get(route('board.meetings.create'))->assertOk();

    $this->post(route('board.meetings.store'), bmData(['agenda' => '<p>1. Finanzen</p>', 'minutes' => '<p>Beschluss: Budget angenommen</p>', 'present' => [$board->id], 'excused' => [$other->id], 'online_link' => 'https://meet.example.org/vorstand']))->assertRedirect();

    $meeting = BoardMeeting::query()->firstOrFail();
    expect($meeting->starts_at->format('H:i'))->toBe('19:00')->and($meeting->attendees)->toHaveCount(2)
        ->and(AuditEntry::query()->where('event', 'board.meeting_saved')->count())->toBe(1);

    $this->get(route('board.meetings.show', $meeting))->assertOk()->assertSee('1. Finanzen')->assertSee('Beschluss: Budget angenommen')->assertSee('Berta Vorstand')->assertSee('Carl Beisitz')->assertSee('Entwurf');
    $this->get(route('board.meetings.index'))->assertOk()->assertSee('Vorstandssitzung Dezember')->assertSee('Anstehende Sitzungen');
});

it('validates the form', function () {
    $this->actingAs(bmUser(RoleKey::BoardMember));

    $this->post(route('board.meetings.store'), bmData(['title' => '']))->assertSessionHasErrors('title');
    $this->post(route('board.meetings.store'), bmData(['end_time' => '18:00']))->assertSessionHasErrors('end_time');
    $this->post(route('board.meetings.store'), bmData(['online_link' => 'http://meet.example.org']))->assertSessionHasErrors('online_link');
    $this->post(route('board.meetings.store'), bmData(['status' => 'x']))->assertSessionHasErrors('status');
    expect(BoardMeeting::query()->count())->toBe(0);
});

it('approves minutes and withdraws the approval when they change', function () {
    $this->actingAs(bmUser(RoleKey::BoardMember));
    $meeting = BoardMeeting::query()->create(['title' => 'Sitzung', 'starts_at' => now()->subDay(), 'ends_at' => now()->subDay()->addHours(2), 'status' => 'held']);

    $this->post(route('board.meetings.approve', $meeting))->assertSessionHas('error');
    expect($meeting->fresh()->minutes_approved_on)->toBeNull();

    $this->put(route('board.meetings.update', $meeting), bmData(['date' => now()->subDay()->format('Y-m-d'), 'status' => 'held', 'minutes' => '<p>Erste Fassung</p>']))->assertRedirect();
    $this->post(route('board.meetings.approve', $meeting))->assertSessionHas('success');
    expect($meeting->fresh()->minutes_approved_on)->not->toBeNull()
        ->and(AuditEntry::query()->where('event', 'board.minutes_approved')->count())->toBe(1);

    $this->put(route('board.meetings.update', $meeting), bmData(['date' => now()->subDay()->format('Y-m-d'), 'status' => 'held', 'minutes' => '<p>Geänderte Fassung</p>']))->assertRedirect();
    expect($meeting->fresh()->minutes_approved_on)->toBeNull();
});

it('deletes single and several meetings', function () {
    $this->actingAs(bmUser(RoleKey::BoardMember));
    $a = BoardMeeting::query()->create(['title' => 'A', 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour()]);
    $b = BoardMeeting::query()->create(['title' => 'B', 'starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addHour()]);
    $c = BoardMeeting::query()->create(['title' => 'C', 'starts_at' => now()->addDays(3), 'ends_at' => now()->addDays(3)->addHour()]);

    $this->delete(route('board.meetings.destroy', $a))->assertRedirect(route('board.meetings.index'));
    $this->post(route('board.meetings.destroy-many'), ['ids' => [$b->id, $c->id]])->assertRedirect();
    $this->post(route('board.meetings.destroy-many'), [])->assertSessionHasErrors('ids');

    expect(BoardMeeting::query()->count())->toBe(0)->and(AuditEntry::query()->where('event', 'board.meeting_deleted')->count())->toBe(3);
});

it('serves the calendar feed only with the secret token', function () {
    $this->actingAs(bmUser(RoleKey::BoardMember));
    $this->post(route('board.meetings.store'), bmData(['location' => 'Büro, 1. Stock; Raum 3', 'online_link' => 'https://meet.example.org/v', 'agenda' => '<p>Geheime Tagesordnung</p>', 'minutes' => '<p>Geheimes Protokoll</p>']))->assertRedirect();
    $this->post(route('board.meetings.store'), bmData(['title' => 'Abgesagt', 'status' => 'cancelled']))->assertRedirect();

    $url = app(BoardCalendar::class)->feedUrl();
    auth()->logout();

    $response = $this->get($url)->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/calendar');
    $ics = $response->getContent();

    expect($ics)->toContain('BEGIN:VCALENDAR')->toContain('SUMMARY:Vorstandssitzung Dezember')->toContain('T190000')->toContain('TZID=Europe/Berlin')
        ->toContain('LOCATION:Büro\, 1. Stock\; Raum 3')->toContain('STATUS:CANCELLED')->toContain("\r\n")
        ->not->toContain('Geheime')->not->toContain('Tagesordnung');

    $this->get(route('board.feed', ['token' => str_repeat('a', 40)]))->assertNotFound();
});

it('renews the feed address so that the old one stops working', function () {
    $this->actingAs(bmUser(RoleKey::BoardMember));
    $old = app(BoardCalendar::class)->feedUrl();
    $this->get($old)->assertOk();

    $this->post(route('board.meetings.feed.renew'))->assertRedirect(route('board.meetings.index'));

    $this->get($old)->assertNotFound();
    $this->get(app(BoardCalendar::class)->feedUrl())->assertOk();
    expect(AuditEntry::query()->where('event', 'board.calendar_changed')->count())->toBe(1);
});

it('folds and escapes ics lines', function () {
    expect(IcsFeed::escape("a,b;c\\d\ne"))->toBe('a\,b\;c\\\\d\ne');

    $folded = IcsFeed::fold('SUMMARY:'.str_repeat('ä', 80));
    foreach (explode("\r\n", $folded) as $line) {
        expect(strlen($line))->toBeLessThanOrEqual(75);
    }
    expect(str_replace("\r\n ", '', $folded))->toBe('SUMMARY:'.str_repeat('ä', 80));
});

it('saves the optional external calendar link and accepts only https', function () {
    $this->actingAs(bmUser(RoleKey::BoardMember));

    foreach (['javascript:alert(1)', 'http://example.org/k', 'kein link'] as $bad) {
        $this->put(route('board.meetings.calendar'), ['calendar_url' => $bad])->assertSessionHasErrors('calendar_url');
    }

    $this->put(route('board.meetings.calendar'), ['calendar_url' => 'https://cloud.example.org/cal/vorstand'])->assertSessionHasNoErrors();
    $this->get(route('board.meetings.index'))->assertOk()->assertSee('https://cloud.example.org/cal/vorstand', false);
});

it('keeps the meetings from everyone but the board', function () {
    $meeting = BoardMeeting::query()->create(['title' => 'Intern', 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour()]);
    $this->get(route('board.meetings.index'))->assertRedirect(route('login'));

    foreach ([RoleKey::AdministrationStaff, RoleKey::Coordination, RoleKey::Team, RoleKey::Member, RoleKey::Administration] as $role) {
        $this->actingAs(bmUser($role));
        $this->get(route('board.meetings.index'))->assertForbidden();
        $this->get(route('board.meetings.show', $meeting))->assertForbidden();
        $this->post(route('board.meetings.store'), bmData())->assertForbidden();
        $this->delete(route('board.meetings.destroy', $meeting))->assertForbidden();
    }

    expect(BoardMeeting::query()->count())->toBe(1);
});

it('lists the meetings in the board navigation', function () {
    $this->actingAs(bmUser(RoleKey::BoardMember))->get(route('board.overview'))->assertOk()->assertSee(route('board.meetings.index'), false);
});
