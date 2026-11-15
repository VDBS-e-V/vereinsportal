<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\People\Models\Person;
use App\Modules\Tickets\Models\Ticket;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

function tkUser(?RoleKey $role = null): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

function tkTicket(User $requester, array $overrides = []): Ticket
{
    return Ticket::query()->create($overrides + ['subject' => 'Passwort geht nicht', 'category' => 'account', 'status' => 'open', 'priority' => 'normal', 'requester_id' => $requester->id, 'last_activity_at' => now()]);
}

it('needs a login', function () {
    $this->get(route('support.mine'))->assertRedirect(route('login'));
    $this->get(route('tickets-admin.index'))->assertRedirect(route('login'));
});

it('creates a ticket with a first message, history and audit entry', function () {
    $user = tkUser(RoleKey::Member);

    $this->actingAs($user)->get(route('support.create'))->assertOk();
    $response = $this->actingAs($user)->post(route('support.store'), ['subject' => 'Kein Zugriff', 'category' => 'account', 'message' => 'Ich komme nicht in den Mitgliederbereich.']);

    $ticket = Ticket::query()->firstOrFail();
    $response->assertRedirect(route('support.show', $ticket))->assertSessionHas('success');

    expect($ticket->status)->toBe('open')->and($ticket->requester_id)->toBe($user->id)->and($ticket->messages)->toHaveCount(1)->and($ticket->events->first()->type)->toBe('created');
    expect(AuditEntry::query()->where('event', 'tickets.created')->exists())->toBeTrue();

    $this->actingAs($user)->get(route('support.mine'))->assertOk()->assertSee('Kein Zugriff')->assertSee($ticket->reference());
});

it('validates new tickets', function () {
    $this->actingAs(tkUser())->post(route('support.store'), ['subject' => '', 'category' => 'x', 'message' => ''])->assertSessionHasErrors(['subject', 'category', 'message']);
});

it('shows a ticket only to its requester and to the team', function () {
    $owner = tkUser(RoleKey::Member);
    $ticket = tkTicket($owner);

    $this->actingAs($owner)->get(route('support.show', $ticket))->assertOk()->assertSee('Passwort geht nicht');
    $this->actingAs(tkUser(RoleKey::Member))->get(route('support.show', $ticket))->assertNotFound();
    $this->actingAs(tkUser(RoleKey::Member))->post(route('support.message', $ticket), ['body' => 'Hallo'])->assertNotFound();
    $this->actingAs(tkUser(RoleKey::AdministrationStaff))->get(route('support.show', $ticket))->assertOk();
    $this->actingAs(tkUser(RoleKey::Member))->get(route('tickets-admin.index'))->assertForbidden();
});

it('lets the team reply, assign and change the status, and logs every change', function () {
    $owner = tkUser(RoleKey::Member);
    $staff = tkUser(RoleKey::AdministrationStaff);
    $ticket = tkTicket($owner);

    $this->actingAs($staff)->post(route('support.message', $ticket), ['body' => 'Wir kümmern uns.'])->assertRedirect();
    expect($ticket->fresh()->status)->toBe('in_progress');

    $this->actingAs($staff)->put(route('tickets-admin.update', $ticket), ['assignee_id' => $staff->id, 'status' => 'waiting', 'priority' => 'high'])->assertRedirect()->assertSessionHasNoErrors();
    $ticket->refresh();
    expect($ticket->assignee_id)->toBe($staff->id)->and($ticket->status)->toBe('waiting')->and($ticket->priority)->toBe('high');
    expect($ticket->events->pluck('type')->all())->toContain('assigned', 'status', 'priority');
    expect(AuditEntry::query()->whereIn('event', ['tickets.assigned', 'tickets.status_changed', 'tickets.message_posted'])->count())->toBeGreaterThanOrEqual(3);

    // Antwort der anfragenden Person öffnet das Ticket wieder für die Bearbeitung
    $this->actingAs($owner)->post(route('support.message', $ticket), ['body' => 'Danke, jetzt geht es.'])->assertRedirect();
    expect($ticket->fresh()->status)->toBe('in_progress');

    // nicht zuweisbar: Personen ohne Bearbeitungsrecht
    $this->actingAs($staff)->put(route('tickets-admin.update', $ticket), ['assignee_id' => $owner->id])->assertSessionHasErrors('assignee_id');
});

it('hides internal notes from the requester', function () {
    $owner = tkUser(RoleKey::Member);
    $staff = tkUser(RoleKey::Administration);
    $ticket = tkTicket($owner);

    $this->actingAs($staff)->post(route('support.message', $ticket), ['body' => 'Geheime Notiz', 'internal' => '1'])->assertRedirect();
    $this->actingAs($staff)->get(route('support.show', $ticket))->assertSee('Geheime Notiz');
    $this->actingAs($owner)->get(route('support.show', $ticket))->assertDontSee('Geheime Notiz');

    // eine interne Notiz ändert den Status nicht
    expect($ticket->fresh()->status)->toBe('open');
    // die anfragende Person kann keine interne Notiz schreiben
    $this->actingAs($owner)->post(route('support.message', $ticket), ['body' => 'Versuch', 'internal' => '1']);
    expect($ticket->messages()->where('body', 'Versuch')->first()->is_internal)->toBeFalse();
});

it('lets the requester close the ticket and reopen it by writing', function () {
    $owner = tkUser(RoleKey::Member);
    $ticket = tkTicket($owner);

    $this->actingAs(tkUser(RoleKey::Member))->post(route('support.close', $ticket))->assertNotFound();
    $this->actingAs($owner)->post(route('support.close', $ticket))->assertRedirect();
    expect($ticket->fresh()->status)->toBe('closed')->and($ticket->fresh()->closed_at)->not->toBeNull();

    $this->actingAs($owner)->post(route('support.message', $ticket), ['body' => 'Doch noch eine Frage'])->assertRedirect();
    expect($ticket->fresh()->status)->toBe('open')->and($ticket->fresh()->closed_at)->toBeNull();
});

it('filters the queue and changes several tickets at once', function () {
    $owner = tkUser(RoleKey::Member);
    $staff = tkUser(RoleKey::AdministrationStaff);
    $a = tkTicket($owner, ['subject' => 'Erstes']);
    $b = tkTicket($owner, ['subject' => 'Zweites']);
    tkTicket($owner, ['subject' => 'Erledigtes', 'status' => 'closed']);

    $this->actingAs($staff)->get(route('tickets-admin.index'))->assertOk()->assertSee('Erstes')->assertSee('Zweites')->assertDontSee('Erledigtes');
    $this->actingAs($staff)->get(route('tickets-admin.index', ['status' => 'all']))->assertSee('Erledigtes');

    $this->actingAs($staff)->post(route('tickets-admin.bulk'), ['ids' => [$a->id, $b->id], 'aktion' => 'assign_me'])->assertRedirect()->assertSessionHas('success');
    expect($a->fresh()->assignee_id)->toBe($staff->id)->and($b->fresh()->status)->toBe('in_progress');

    $this->actingAs($staff)->get(route('tickets-admin.index', ['assignee' => 'me']))->assertSee('Erstes');
    $this->actingAs($staff)->post(route('tickets-admin.bulk'), ['ids' => [$a->id], 'aktion' => 'status'])->assertSessionHasErrors('status');
    $this->actingAs($staff)->post(route('tickets-admin.bulk'), ['aktion' => 'assign_me'])->assertSessionHasErrors('ids');
    $this->actingAs($staff)->post(route('tickets-admin.bulk'), ['ids' => [$a->id], 'aktion' => 'status', 'status' => 'resolved'])->assertRedirect();
    expect($a->fresh()->status)->toBe('resolved');
});

it('shows the ticket pages in the right places of the menu', function () {
    $this->actingAs(tkUser(RoleKey::AdministrationStaff))->get(route('office.home'))->assertOk()->assertSee('Tickets');
    $this->actingAs(tkUser(RoleKey::Member))->get(route('account.home'))->assertSee('Meine Tickets')->assertSee('Meine Anmeldungen');
});
