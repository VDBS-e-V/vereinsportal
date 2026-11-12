<?php

use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Events\Mail\EventRegisteredMail;
use App\Modules\Events\Models\Event;
use App\Modules\Events\Models\EventRegistration;
use App\Modules\Events\Services\EventAdministration;
use App\Modules\Partners\Models\Partner;
use App\Modules\Partners\Models\PartnerContact;
use App\Modules\Partners\Models\PartnerUser;
use App\Modules\Partners\Services\SchoolAccounts;
use App\Modules\People\Models\Person;
use App\Support\PortalAreas;
use App\Support\PortalNavigation;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Mail::fake();
});

function slCoordination(): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);
    RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', RoleKey::Coordination->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);

    return $user;
}

function slAccount(string $schoolName = 'Rosa-Luxemburg-Schule', string $email = 'lehrkraft@schule.example'): PartnerUser
{
    $partner = Partner::factory()->create(['name' => $schoolName]);
    $contact = PartnerContact::query()->create(['partner_id' => $partner->id, 'name' => 'Frau Lehrkraft', 'email' => $email]);
    $account = app(SchoolAccounts::class)->invite($partner, $contact, slCoordination());
    $account->user->forceFill(['status' => UserStatus::Active])->save();

    return $account->fresh(['user', 'partner']);
}

function slEvent(array $attributes = []): Event
{
    $event = app(EventAdministration::class)->create($attributes + ['title' => 'Demokratietag Nord', 'status' => 'open'], slCoordination());
    app(EventAdministration::class)->addSession($event, ['date' => now()->addMonth()->toDateString(), 'start' => '09:00', 'end' => '15:00']);

    return $event->refresh();
}

function slCsv(array $rows, string $header = "Name;Klasse;Geburtsdatum;E-Mail\n"): UploadedFile
{
    return UploadedFile::fake()->createWithContent('liste.csv', $header.implode("\n", $rows)."\n");
}

it('lists the public events for school accounts, without drafts, and keeps others out', function () {
    $account = slAccount();
    slEvent(['title' => 'Öffentlicher Tag']);
    slEvent(['title' => 'Interner Entwurf', 'status' => 'draft']);

    $this->get(route('school.events.index'))->assertRedirect(route('login'));
    $this->actingAs(slCoordination())->get(route('school.events.index'))->assertForbidden();

    $this->actingAs($account->user)->get(route('school.events.index'))->assertOk()->assertSee('Öffentlicher Tag')->assertDontSee('Interner Entwurf');
    expect(array_column(PortalNavigation::pages(PortalAreas::Schulen, $account->user), 'label'))->toBe(['Übersicht', 'Seminar anfragen', 'Veranstaltungen']);

    $draft = Event::query()->where('title', 'Interner Entwurf')->firstOrFail();
    $this->actingAs($account->user)->get(route('school.events.show', $draft))->assertNotFound();
});

it('lets a school sign up a list of its pupils without the school column and mails the confirmations', function () {
    $account = slAccount('Rosa-Luxemburg-Schule');
    $event = slEvent();

    $this->actingAs($account->user)->get(route('school.events.show', $event))->assertOk()->assertSee('Liste hochladen')->assertSee('Noch niemand angemeldet');
    $this->actingAs($account->user)->get(route('school.events.template', $event))->assertOk();

    $this->actingAs($account->user)->post(route('school.events.import', $event), ['file' => slCsv(['Mia Beispiel;9b;14.05.2010;lehrkraft@schule.example', 'Ben Muster;9b;02.11.2011;lehrkraft@schule.example']), 'send_mails' => '1'])
        ->assertRedirect(route('school.events.show', $event))->assertSessionHas('success');

    $rows = $event->registrations()->get();
    expect($rows)->toHaveCount(2)->and($rows->pluck('school')->unique()->all())->toBe(['Rosa-Luxemburg-Schule'])->and($rows->pluck('partner_id')->unique()->all())->toBe([$account->partner_id])
        ->and($rows->every(fn (EventRegistration $row): bool => $row->isConfirmed() && $row->source === 'upload'))->toBeTrue();
    Mail::assertSent(EventRegisteredMail::class, 2);

    $this->actingAs($account->user)->get(route('school.events.show', $event))->assertSee('Mia Beispiel')->assertSee('Angemeldet von Ihrer Schule (2)');
});

it('overrides a school column in the file and refuses faulty lists completely', function () {
    $account = slAccount('Rosa-Luxemburg-Schule');
    $event = slEvent();

    $this->actingAs($account->user)->post(route('school.events.import', $event), ['file' => slCsv(['Mia Beispiel;Andere Schule;9b;14.05.2010;a@example.org'], "Name;Schule;Klasse;Geburtsdatum;E-Mail\n")])->assertSessionHasNoErrors();
    expect($event->registrations()->value('school'))->toBe('Rosa-Luxemburg-Schule');

    $this->actingAs($account->user)->post(route('school.events.import', $event), ['file' => slCsv(['Gut Gut;9b;14.05.2010;b@example.org', ';9b;kaputt;kaputt'])])->assertRedirect(route('school.events.show', $event))->assertSessionHas('import_errors');
    expect($event->registrations()->count())->toBe(1);

    $this->actingAs($account->user)->post(route('school.events.import', $event), ['file' => slCsv(['x'], "Foo;Bar\n")])->assertSessionHas('import_errors');
});

it('respects the capacity and the status of the event', function () {
    $account = slAccount();
    $small = slEvent(['title' => 'Klein', 'capacity' => 1]);
    $closed = slEvent(['title' => 'Zu', 'status' => 'closed']);
    $late = slEvent(['title' => 'Spät', 'registration_deadline' => now()->subDay()->format('Y-m-d H:i:s')]);

    $this->actingAs($account->user)->post(route('school.events.import', $small), ['file' => slCsv(['A A;9;01.01.2010;a@example.org', 'B B;9;01.01.2010;b@example.org'])])->assertSessionHas('import_errors');
    expect($small->registrations()->count())->toBe(0);

    foreach ([$closed, $late] as $event) {
        $this->actingAs($account->user)->post(route('school.events.import', $event), ['file' => slCsv(['A A;9;01.01.2010;a@example.org'])])->assertSessionHasErrors('school');
        expect($event->registrations()->count())->toBe(0);
        $this->actingAs($account->user)->get(route('school.events.show', $event))->assertOk()->assertSee('keine Listen mehr');
    }
});

it('lets a school remove only its own registrations', function () {
    $account = slAccount('Schule Eins');
    $other = slAccount('Schule Zwei', 'zwei@schule.example');
    $event = slEvent();

    $this->actingAs($account->user)->post(route('school.events.import', $event), ['file' => slCsv(['Mia Beispiel;9b;14.05.2010;a@example.org'])]);
    $this->actingAs($other->user)->post(route('school.events.import', $event), ['file' => slCsv(['Ben Muster;8a;02.11.2011;b@example.org'])]);

    $mine = $event->registrations()->where('name', 'Mia Beispiel')->firstOrFail();
    $foreign = $event->registrations()->where('name', 'Ben Muster')->firstOrFail();

    $this->actingAs($account->user)->get(route('school.events.show', $event))->assertSee('Mia Beispiel')->assertDontSee('Ben Muster');
    $this->actingAs($account->user)->delete(route('school.events.registrations.destroy', [$event, $foreign]))->assertNotFound();
    $this->actingAs($account->user)->delete(route('school.events.registrations.destroy', [$event, $mine]))->assertRedirect(route('school.events.show', $event));

    expect($event->registrations()->pluck('name')->all())->toBe(['Ben Muster']);
});
