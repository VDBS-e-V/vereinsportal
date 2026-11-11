<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\Events\Mail\EventMessageMail;
use App\Modules\Events\Models\Event;
use App\Modules\Events\Models\EventMessage;
use App\Modules\Events\Services\EventAdministration;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Mail::fake();
});

function emUser(?RoleKey $role = null): User
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

function emEvent(): Event
{
    $event = app(EventAdministration::class)->create(['title' => 'Demokratietag Nord', 'status' => 'open', 'waitlist_enabled' => true], emUser(RoleKey::Coordination));

    foreach ([['Mia Beispiel', 'klasse@schule.example', false, true], ['Ben Muster', 'klasse@schule.example', false, true], ['Cleo Dritte', 'cleo@example.org', false, true], ['Wartend Eins', 'wartend@example.org', true, true], ['Unbestätigt', 'offen@example.org', false, false]] as $i => [$name, $email, $waiting, $verified]) {
        $event->registrations()->create(['name' => $name, 'school' => 'S', 'class_name' => '9', 'birth_date' => '2010-01-01', 'email' => $email, 'ticket_code' => 'EMAB'.$i.'2345', 'waitlisted' => $waiting, 'verified_at' => $verified ? now() : null]);
    }

    return $event;
}

it('sends a message once per address to the confirmed registrations and stores it without recipients', function () {
    $event = emEvent();
    $coordination = emUser(RoleKey::Coordination);

    $this->actingAs($coordination)->get(route('events.admin.messages.create', $event))->assertOk()->assertSee('2 Adressen')->assertSee('1 Adressen');

    $this->actingAs($coordination)->post(route('events.admin.messages.store', $event), ['audience' => 'confirmed', 'subject' => 'Raumänderung', 'body' => "Wir treffen uns in der Aula.\nBitte pünktlich sein."])->assertRedirect(route('events.admin.show', $event))->assertSessionHas('success');

    Mail::assertSent(EventMessageMail::class, 2);
    Mail::assertSent(EventMessageMail::class, fn (EventMessageMail $mail): bool => $mail->hasTo('klasse@schule.example') && $mail->subjectLine === 'Raumänderung' && str_contains($mail->render(), 'Wir treffen uns in der Aula.'));
    Mail::assertSent(EventMessageMail::class, fn (EventMessageMail $mail): bool => $mail->hasTo('cleo@example.org'));
    Mail::assertNotSent(EventMessageMail::class, fn (EventMessageMail $mail): bool => $mail->hasTo('wartend@example.org') || $mail->hasTo('offen@example.org'));

    $message = EventMessage::query()->firstOrFail();
    expect($message->recipients_count)->toBe(2)->and($message->audience)->toBe('confirmed')->and($message->sent_by_user_id)->toBe($coordination->id);

    $this->actingAs($coordination)->get(route('events.admin.show', $event))->assertSee('Gesendete Mitteilungen')->assertSee('Raumänderung');
});

it('can address the waitlist or everyone', function () {
    $event = emEvent();
    $coordination = emUser(RoleKey::Coordination);

    $this->actingAs($coordination)->post(route('events.admin.messages.store', $event), ['audience' => 'waitlist', 'subject' => 'Plätze', 'body' => 'Es könnte klappen.']);
    Mail::assertSent(EventMessageMail::class, 1);
    Mail::assertSent(EventMessageMail::class, fn (EventMessageMail $mail): bool => $mail->hasTo('wartend@example.org'));

    $this->actingAs($coordination)->post(route('events.admin.messages.store', $event), ['audience' => 'both', 'subject' => 'Alle', 'body' => 'Hallo zusammen.']);
    Mail::assertSent(EventMessageMail::class, 4);
});

it('escapes the text and keeps the log free of recipients and text', function () {
    $event = emEvent();
    $coordination = emUser(RoleKey::Coordination);

    $this->actingAs($coordination)->post(route('events.admin.messages.store', $event), ['audience' => 'confirmed', 'subject' => 'Hinweis', 'body' => 'Bitte <script>alert(1)</script> lesen']);

    Mail::assertSent(EventMessageMail::class, fn (EventMessageMail $mail): bool => ! str_contains($mail->render(), '<script>'));

    $entry = AuditEntry::query()->where('event', 'event.message_sent')->firstOrFail();
    expect(json_encode($entry->context, JSON_UNESCAPED_UNICODE))->toContain('Demokratietag Nord')->not->toContain('example')->not->toContain('script')->not->toContain('Mia');
});

it('validates the message and refuses empty groups', function () {
    $event = emEvent();
    $coordination = emUser(RoleKey::Coordination);

    $this->actingAs($coordination)->post(route('events.admin.messages.store', $event), ['audience' => 'kaputt', 'subject' => '', 'body' => ''])->assertSessionHasErrors(['audience', 'subject', 'body']);

    $empty = app(EventAdministration::class)->create(['title' => 'Leer', 'status' => 'open'], $coordination);
    $this->actingAs($coordination)->post(route('events.admin.messages.store', $empty), ['audience' => 'confirmed', 'subject' => 'Hallo', 'body' => 'Text'])->assertSessionHasErrors('audience');

    Mail::assertNothingSent();
    expect(EventMessage::query()->count())->toBe(0);
});

it('lets only those who manage events send messages', function () {
    $event = emEvent();

    $this->get(route('events.admin.messages.create', $event))->assertRedirect(route('login'));
    foreach ([null, RoleKey::Team, RoleKey::EducationCoordination] as $role) {
        $this->actingAs(emUser($role))->get(route('events.admin.messages.create', $event))->assertForbidden();
        $this->actingAs(emUser($role))->post(route('events.admin.messages.store', $event), ['audience' => 'confirmed', 'subject' => 'x', 'body' => 'y'])->assertForbidden();
    }

    Mail::assertNothingSent();
});
