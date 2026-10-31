<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\Events\Enums\EventStatus;
use App\Modules\Events\Mail\EventConfirmMail;
use App\Modules\Events\Mail\EventRegisteredMail;
use App\Modules\Events\Models\Event;
use App\Modules\Events\Models\EventAttendance;
use App\Modules\Events\Models\EventRegistration;
use App\Modules\Events\Services\EventAdministration;
use App\Modules\Events\Services\EventRegistrations;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Mail::fake();
});

function evUser(?RoleKey $role = null): User
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

function evEvent(array $attributes = [], bool $session = true): Event
{
    $event = app(EventAdministration::class)->create($attributes + ['title' => 'Demokratietag Nord', 'status' => 'open', 'capacity' => null], evUser(RoleKey::Coordination));

    if ($session) {
        app(EventAdministration::class)->addSession($event, ['date' => now()->addMonth()->toDateString(), 'start' => '09:00', 'end' => '15:00']);
    }

    return $event->refresh();
}

function evForm(array $overrides = []): array
{
    return $overrides + ['name' => 'Mia Beispiel', 'school' => 'Rosa-Luxemburg-Schule', 'class_name' => '9b', 'birth_date' => '2010-05-14', 'email' => 'mia@example.org', 'consent' => '1'];
}

function evCsv(array $rows, string $header = "Name;Schule;Klasse;Geburtsdatum;E-Mail\n"): UploadedFile
{
    return UploadedFile::fake()->createWithContent('anmeldungen.csv', $header.implode("\n", $rows)."\n");
}

it('lists public events without drafts and shows the registration form', function () {
    evEvent(['title' => 'Öffentlicher Tag']);
    evEvent(['title' => 'Interner Entwurf', 'status' => 'draft']);

    $this->get(route('events.public.index'))->assertOk()->assertSee('Öffentlicher Tag')->assertDontSee('Interner Entwurf');
    $this->get(route('events.public.show', Event::query()->where('title', 'Interner Entwurf')->value('slug')))->assertNotFound();
    $this->get(route('events.public.show', Event::query()->where('title', 'Öffentlicher Tag')->value('slug')))->assertOk()->assertSee('Anmelden')->assertSee('Geburtsdatum');
});

it('puts the bot guard into the registration and certificate forms', function () {
    config(['portal.bot_guard.enabled' => true]);
    $event = evEvent();

    $this->get(route('events.public.show', $event->slug))->assertSee('data-vdbs-bot-guard', false);
    $this->get(route('events.public.certificate'))->assertSee('data-vdbs-bot-guard', false);
});

it('registers with double opt-in, ticket code and an automatic confirmation', function () {
    $event = evEvent();

    $this->post(route('events.public.register', $event->slug), evForm())->assertRedirect(route('events.public.show', $event->slug))->assertSessionHas('success');

    $registration = EventRegistration::query()->firstOrFail();
    expect($registration->isConfirmed())->toBeFalse()->and($registration->ticket_code)->toHaveLength(8)->and($registration->source)->toBe('self');
    Mail::assertSent(EventConfirmMail::class, fn (EventConfirmMail $mail): bool => $mail->hasTo('mia@example.org'));
    Mail::assertNotSent(EventRegisteredMail::class);

    $url = null;
    Mail::assertSent(EventConfirmMail::class, function (EventConfirmMail $mail) use (&$url): bool {
        $url = $mail->url;

        return true;
    });

    $this->get($url)->assertOk()->assertSee($registration->ticket());

    expect($registration->fresh()->isConfirmed())->toBeTrue();
    Mail::assertSent(EventRegisteredMail::class, fn (EventRegisteredMail $mail): bool => $mail->hasTo('mia@example.org') && $mail->registration->is($registration));
});

it('rejects unsigned and expired confirmation links', function () {
    $event = evEvent();
    $this->post(route('events.public.register', $event->slug), evForm());
    $registration = EventRegistration::query()->firstOrFail();

    $this->get(route('events.public.confirm', $registration))->assertForbidden();

    $registration->forceFill(['created_at' => now()->subHours(49)])->save();
    $url = \Illuminate\Support\Facades\URL::temporarySignedRoute('events.public.confirm', now()->addHour(), ['registration' => $registration->id]);

    $this->get($url)->assertOk()->assertSee('nicht mehr gültig');
    expect($registration->fresh()->isConfirmed())->toBeFalse();
});

it('answers the same for known registrations and resends instead of duplicating', function () {
    $event = evEvent();

    $this->post(route('events.public.register', $event->slug), evForm());
    $this->post(route('events.public.register', $event->slug), evForm())->assertSessionHas('success');

    expect(EventRegistration::query()->count())->toBe(1);
    Mail::assertSent(EventConfirmMail::class, 2);
});

it('validates the registration and requires the consent', function () {
    $event = evEvent();

    $this->post(route('events.public.register', $event->slug), evForm(['name' => '', 'birth_date' => '2999-01-01', 'email' => 'kaputt', 'consent' => null]))
        ->assertSessionHasErrors(['name', 'birth_date', 'email', 'consent']);
    expect(EventRegistration::query()->count())->toBe(0);
});

it('stops registrations when the event is full, closed or past its deadline', function () {
    $full = evEvent(['title' => 'Klein', 'capacity' => 1]);
    $this->post(route('events.public.register', $full->slug), evForm())->assertSessionHas('success');
    $this->post(route('events.public.register', $full->slug), evForm(['name' => 'Zweite Person', 'email' => 'zwei@example.org']))->assertSessionHasErrors('registration');
    expect(EventRegistration::query()->count())->toBe(1);

    $closed = evEvent(['title' => 'Zu', 'status' => 'closed']);
    $this->post(route('events.public.register', $closed->slug), evForm(['email' => 'x@example.org']))->assertSessionHasErrors('registration');

    $late = evEvent(['title' => 'Spät', 'registration_deadline' => now()->subDay()->format('Y-m-d H:i:s')]);
    $this->post(route('events.public.register', $late->slug), evForm(['email' => 'y@example.org']))->assertSessionHasErrors('registration');
});

it('counts only confirmed and fresh pending registrations as taken spots', function () {
    $event = evEvent(['capacity' => 2]);
    $this->post(route('events.public.register', $event->slug), evForm());
    expect($event->takenSpots())->toBe(1);

    EventRegistration::query()->firstOrFail()->forceFill(['created_at' => now()->subHours(60)])->save();
    expect($event->takenSpots())->toBe(0);
});

it('keeps everyone but the coordination out of the administration', function () {
    $event = evEvent();

    $this->get(route('events.admin.index'))->assertRedirect(route('login'));

    foreach ([null, RoleKey::Team, RoleKey::Member] as $role) {
        $this->actingAs(evUser($role))->get(route('events.admin.index'))->assertForbidden();
        $this->actingAs(evUser($role))->get(route('events.admin.show', $event))->assertForbidden();
    }

    foreach ([RoleKey::Coordination, RoleKey::AdministrationStaff, RoleKey::BoardMember] as $role) {
        $this->actingAs(evUser($role))->get(route('events.admin.index'))->assertOk()->assertSee('Demokratietag Nord');
    }
});

it('creates, changes and deletes events with a log without names', function () {
    $coordination = evUser(RoleKey::Coordination);

    $this->actingAs($coordination)->post(route('events.admin.store'), ['title' => 'Medientag', 'status' => 'draft', 'capacity' => '120', 'location' => 'Aula', 'description' => 'Ein Tag'])->assertSessionHasNoErrors();
    $event = Event::query()->where('title', 'Medientag')->firstOrFail();
    expect($event->slug)->toStartWith('medientag')->and($event->capacity)->toBe(120);

    $this->actingAs($coordination)->put(route('events.admin.update', $event), ['title' => 'Medientag', 'status' => 'open', 'capacity' => '100'])->assertSessionHasNoErrors();
    expect($event->fresh()->status)->toBe(EventStatus::Open);

    expect(AuditEntry::query()->whereIn('event', ['event.created', 'event.updated'])->count())->toBe(2);

    $this->actingAs($coordination)->post(route('events.admin.sessions.store', $event), ['date' => now()->addMonth()->toDateString(), 'start' => '10:00', 'end' => '09:00'])->assertSessionHasErrors('end');
    $this->actingAs($coordination)->post(route('events.admin.sessions.store', $event), ['date' => now()->addMonth()->toDateString(), 'start' => '09:00', 'end' => '15:00'])->assertSessionHasNoErrors();
    expect($event->sessions()->count())->toBe(1);

    $this->actingAs($coordination)->delete(route('events.admin.destroy', $event))->assertRedirect(route('events.admin.index'));
    expect(Event::query()->count())->toBe(0);

    $this->actingAs($coordination)->post(route('events.admin.store'), ['title' => '', 'status' => 'kaputt'])->assertSessionHasErrors(['title', 'status']);
});

it('does not delete events that have registrations', function () {
    $event = evEvent();
    $this->post(route('events.public.register', $event->slug), evForm());

    $this->actingAs(evUser(RoleKey::Coordination))->delete(route('events.admin.destroy', $event))->assertSessionHasErrors('event');
    expect(Event::query()->count())->toBe(1);
});

it('imports a list on behalf of teachers, all or nothing, with confirmations by mail', function () {
    $event = evEvent();
    $coordination = evUser(RoleKey::Coordination);

    $this->actingAs($coordination)->post(route('events.admin.import.store', $event), ['file' => evCsv(['Mia Beispiel;Schule A;9b;14.05.2010;mia@example.org', 'Ben Muster;Schule B;8a;02.11.2011;ben@example.org']), 'send_mails' => '1'])->assertRedirect(route('events.admin.show', $event));

    expect($event->registrations()->count())->toBe(2)->and($event->registrations()->whereNotNull('verified_at')->count())->toBe(2)->and($event->registrations()->value('source'))->toBe('upload');
    Mail::assertSent(EventRegisteredMail::class, 2);

    $entry = AuditEntry::query()->where('event', 'event.registrations_imported')->firstOrFail();
    expect(json_encode($entry->context, JSON_UNESCAPED_UNICODE))->toContain('2')->not->toContain('Mia')->not->toContain('example.org');
});

it('imports nothing when one row is wrong and names every problem', function () {
    $event = evEvent();

    $this->actingAs(evUser(RoleKey::Coordination))->post(route('events.admin.import.store', $event), ['file' => evCsv(['Mia Beispiel;Schule A;9b;14.05.2010;mia@example.org', ';Schule B;8a;99.99.2011;kaputt', 'Mia Beispiel;Schule A;9b;14.05.2010;mia@example.org'])])
        ->assertRedirect(route('events.admin.import', $event))->assertSessionHas('import_errors');

    expect($event->registrations()->count())->toBe(0);
    $errors = session('import_errors');
    expect(implode(' ', $errors))->toContain('Zeile 3')->toContain('Zeile 4');
});

it('refuses a list that does not fit the free places or has the wrong header', function () {
    $event = evEvent(['capacity' => 1]);
    $user = evUser(RoleKey::Coordination);

    $this->actingAs($user)->post(route('events.admin.import.store', $event), ['file' => evCsv(['A A;S;1a;01.01.2010;a@example.org', 'B B;S;1a;01.01.2010;b@example.org'])])->assertSessionHas('import_errors');
    $this->actingAs($user)->post(route('events.admin.import.store', $event), ['file' => evCsv(['x'], "Foo;Bar\n")])->assertSessionHas('import_errors');
    expect($event->registrations()->count())->toBe(0);
});

it('tracks attendance by list and by ticket code and keeps viewers read-only', function () {
    $event = evEvent();
    $session = $event->sessions()->firstOrFail();
    $coordination = evUser(RoleKey::Coordination);
    $this->actingAs($coordination)->post(route('events.admin.import.store', $event), ['file' => evCsv(['Mia Beispiel;Schule A;9b;14.05.2010;mia@example.org', 'Ben Muster;Schule B;8a;02.11.2011;ben@example.org'])]);
    $mia = $event->registrations()->where('name', 'Mia Beispiel')->firstOrFail();
    $ben = $event->registrations()->where('name', 'Ben Muster')->firstOrFail();

    $this->get(route('events.admin.attendance', [$event, $session]))->assertOk()->assertSee('Mia Beispiel')->assertSee('0 anwesend');

    $this->post(route('events.admin.attendance.mark', [$event, $session]), ['registration' => $mia->id, 'present' => 1])->assertRedirect();
    expect(EventAttendance::query()->count())->toBe(1);

    $this->post(route('events.admin.attendance.ticket', [$event, $session]), ['ticket' => strtolower(substr($ben->ticket_code, 0, 4)).' - '.substr($ben->ticket_code, 4)])->assertSessionHas('success');
    expect(EventAttendance::query()->count())->toBe(2);

    $this->post(route('events.admin.attendance.ticket', [$event, $session]), ['ticket' => 'GIBTESNICHT'])->assertSessionHas('ticket_unknown');

    $this->post(route('events.admin.attendance.mark', [$event, $session]), ['registration' => $mia->id, 'present' => 0]);
    expect(EventAttendance::query()->count())->toBe(1);

    $this->get(route('events.admin.attendance', [$event, $session, 'q' => 'Muster']))->assertSee('Ben Muster')->assertDontSee('Mia Beispiel');
});

it('gives the certificate only with ticket, matching email and a recorded attendance', function () {
    $event = evEvent();
    $session = $event->sessions()->firstOrFail();
    $this->actingAs(evUser(RoleKey::Coordination))->post(route('events.admin.import.store', $event), ['file' => evCsv(['Mia Beispiel;Schule A;9b;14.05.2010;mia@example.org'])]);
    $mia = $event->registrations()->firstOrFail();
    $this->app['auth']->forgetGuards();

    // noch keine Anwesenheit
    $this->post(route('events.public.certificate.show'), ['ticket' => $mia->ticket(), 'email' => 'mia@example.org'])->assertSessionHasErrors('ticket');

    app(EventRegistrations::class)->mark($session, $mia, true, evUser(RoleKey::Coordination));

    $this->post(route('events.public.certificate.show'), ['ticket' => $mia->ticket(), 'email' => 'fremd@example.org'])->assertSessionHasErrors('ticket');
    $this->post(route('events.public.certificate.show'), ['ticket' => 'FALSCH123', 'email' => 'mia@example.org'])->assertSessionHasErrors('ticket');

    $this->post(route('events.public.certificate.show'), ['ticket' => strtolower($mia->ticket()), 'email' => 'MIA@example.org'])
        ->assertOk()->assertSee('Teilnahmebestätigung')->assertSee('Mia Beispiel')->assertSee('Demokratietag Nord')->assertSee($session->starts_at->format('d.m.Y'))->assertDontSee('14.05.2010');
});

it('lets the coordination open the certificate of a registration and remove registrations', function () {
    $event = evEvent();
    $coordination = evUser(RoleKey::Coordination);
    $this->actingAs($coordination)->post(route('events.admin.import.store', $event), ['file' => evCsv(['Mia Beispiel;Schule A;9b;14.05.2010;mia@example.org'])]);
    $mia = $event->registrations()->firstOrFail();
    app(EventRegistrations::class)->mark($event->sessions()->firstOrFail(), $mia, true, $coordination);

    $this->actingAs($coordination)->get(route('events.admin.registrations.certificate', [$event, $mia]))->assertOk()->assertSee('Mia Beispiel');

    $this->actingAs($coordination)->delete(route('events.admin.registrations.destroy', [$event, $mia]))->assertRedirect(route('events.admin.show', $event));
    expect($event->registrations()->count())->toBe(0);

    $entry = AuditEntry::query()->where('event', 'event.registration_removed')->firstOrFail();
    expect(json_encode($entry->context, JSON_UNESCAPED_UNICODE))->not->toContain('Mia');
});

it('exports the list as CSV without formulas', function () {
    $event = evEvent();
    $coordination = evUser(RoleKey::Coordination);
    $this->actingAs($coordination)->post(route('events.admin.import.store', $event), ['file' => evCsv(['=SUMME(A1);Schule A;9b;14.05.2010;mia@example.org'])]);

    $body = $this->actingAs($coordination)->get(route('events.admin.export', $event))->assertOk()->streamedContent();

    expect($body)->toContain('Ticketcode')->toContain("'=SUMME(A1)")->toContain('mia@example.org');
});

it('searches registrations in the administration', function () {
    $event = evEvent();
    $coordination = evUser(RoleKey::Coordination);
    $this->actingAs($coordination)->post(route('events.admin.import.store', $event), ['file' => evCsv(['Mia Beispiel;Schule A;9b;14.05.2010;mia@example.org', 'Ben Muster;Schule B;8a;02.11.2011;ben@example.org'])]);

    $this->actingAs($coordination)->get(route('events.admin.show', [$event, 'q' => 'Schule B']))->assertSee('Ben Muster')->assertDontSee('Mia Beispiel');
});

it('prunes pending registrations after the deadline and registrations after the retention period', function () {
    $event = evEvent();
    $this->post(route('events.public.register', $event->slug), evForm());
    $this->post(route('events.public.register', $event->slug), evForm(['name' => 'Ben Muster', 'email' => 'ben@example.org']));
    EventRegistration::query()->where('name', 'Mia Beispiel')->update(['created_at' => now()->subHours(50)]);

    $this->artisan('events:prune', ['--dry-run' => true])->expectsOutputToContain('Unbestätigte Anmeldungen: 1')->assertSuccessful();
    expect(EventRegistration::query()->count())->toBe(2);

    $this->artisan('events:prune')->assertSuccessful();
    expect(EventRegistration::query()->pluck('name')->all())->toBe(['Ben Muster']);

    $old = evEvent(['title' => 'Vor Jahren'], false);
    app(EventAdministration::class)->addSession($old, ['date' => now()->subYears(4)->toDateString(), 'start' => '09:00', 'end' => '12:00']);
    $old->registrations()->create(['name' => 'Alt', 'school' => 'S', 'class_name' => '5', 'birth_date' => '2008-01-01', 'email' => 'alt@example.org', 'ticket_code' => 'ABCD2345', 'verified_at' => now()->subYears(4)]);

    $this->artisan('events:prune')->assertSuccessful();
    expect(EventRegistration::query()->where('name', 'Alt')->exists())->toBeFalse()->and(EventRegistration::query()->where('name', 'Ben Muster')->exists())->toBeTrue();
});

it('shows the events pages in the navigation', function () {
    $this->actingAs(evUser(RoleKey::Coordination))->get(route('events.admin.index'))->assertOk()->assertSee('Veranstaltungen');
    $this->get(route('events.public.index'))->assertOk();
});
