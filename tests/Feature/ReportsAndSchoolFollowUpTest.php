<?php

use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Events\Mail\SchoolEventReminderMail;
use App\Modules\Events\Models\EventAttendance;
use App\Modules\Events\Services\EventAdministration;
use App\Modules\Membership\Models\Membership;
use App\Modules\Partners\Models\Partner;
use App\Modules\Partners\Models\PartnerContact;
use App\Modules\Partners\Models\PartnerUser;
use App\Modules\Partners\Services\SchoolAccounts;
use App\Modules\People\Models\Person;
use App\Modules\Seminars\Enums\ApplicationStatus;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use App\Modules\Seminars\Services\SeminarAdministration;
use App\Support\PortalAreas;
use App\Support\PortalNavigation;
use App\Support\YearReport;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Mail::fake();
});

function rfUser(?RoleKey $role = null): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

function rfSchoolAccount(string $name = 'Schule Eins', string $email = 'eins@schule.example'): PartnerUser
{
    $partner = Partner::factory()->create(['name' => $name]);
    $contact = PartnerContact::query()->create(['partner_id' => $partner->id, 'name' => 'Frau Lehrkraft', 'email' => $email]);
    $account = app(SchoolAccounts::class)->invite($partner, $contact, rfUser(RoleKey::Coordination));
    $account->user->forceFill(['status' => UserStatus::Active])->save();

    return $account->fresh(['user', 'partner']);
}

function rfValue(array $rows, string $group, string $label): int|string
{
    return collect($rows)->first(fn (array $row): bool => $row['group'] === $group && $row['label'] === $label)['value'];
}

// ---------- Jahresbericht ----------

it('computes the year report from seminars, events, teams and members', function () {
    $coordination = rfUser(RoleKey::Coordination);
    $year = (int) now()->year;
    $date = $year.'-03-10';

    $school = Partner::factory()->create();
    foreach ([['sv_days', SeminarStatus::Done], ['coaching', SeminarStatus::Completed], ['sv_days', SeminarStatus::Planning]] as [$type, $status]) {
        $seminar = Seminar::factory()->create(['type' => $type, 'status' => $status, 'partner_id' => $school->id]);
        app(SeminarAdministration::class)->addSession($seminar, ['date' => $date, 'start' => '09:00', 'end' => '15:00'], $coordination);
        app(SeminarAdministration::class)->addSession($seminar, ['date' => $year.'-03-11', 'start' => '09:00', 'end' => '15:00'], $coordination);

        if ($status === SeminarStatus::Done) {
            SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'user_id' => rfUser(RoleKey::Team)->id, 'status' => ApplicationStatus::Confirmed]);
        }
    }

    // Seminar aus dem Vorjahr zählt nicht
    $old = Seminar::factory()->create(['status' => SeminarStatus::Done]);
    app(SeminarAdministration::class)->addSession($old, ['date' => ($year - 1).'-05-05', 'start' => '09:00', 'end' => '12:00'], $coordination);

    $event = app(EventAdministration::class)->create(['title' => 'Demokratietag', 'status' => 'done'], $coordination);
    $session = app(EventAdministration::class)->addSession($event, ['date' => $date, 'start' => '09:00', 'end' => '15:00']);
    foreach (['A', 'B', 'C'] as $i => $name) {
        $registration = $event->registrations()->create(['name' => $name, 'school' => $i === 2 ? 'Schule Zwei' : 'Schule Eins', 'class_name' => '9', 'birth_date' => '2010-01-01', 'email' => $name.'@example.org', 'ticket_code' => 'RPT'.$i.'12345', 'verified_at' => now(), 'waitlisted' => false]);

        if ($i < 2) {
            EventAttendance::query()->create(['registration_id' => $registration->id, 'session_id' => $session->id]);
        }
    }
    $event->registrations()->create(['name' => 'W', 'school' => 'Schule Drei', 'class_name' => '9', 'birth_date' => '2010-01-01', 'email' => 'w@example.org', 'ticket_code' => 'RPTW12345', 'verified_at' => now(), 'waitlisted' => true]);

    Membership::factory()->create(['starts_on' => ($year - 2).'-01-01']);
    Membership::factory()->create(['starts_on' => $year.'-02-01']);
    Membership::factory()->create(['starts_on' => ($year - 3).'-01-01', 'ends_on' => $year.'-06-30']);
    Membership::factory()->create(['starts_on' => ($year - 3).'-01-01', 'ends_on' => ($year - 1).'-06-30', 'status' => 'ended']);

    $rows = YearReport::rows($year);

    expect(rfValue($rows, 'Seminare', 'Durchgeführte Seminare'))->toBe(2)
        ->and(rfValue($rows, 'Seminare', 'Seminartage (Termine)'))->toBe(4)
        ->and(rfValue($rows, 'Seminare', 'Beteiligte Schulen'))->toBe(1)
        ->and(rfValue($rows, 'Seminare', 'SV-Tage'))->toBe(1)
        ->and(rfValue($rows, 'Teamende', 'Teamende im Einsatz'))->toBe(1)
        ->and(rfValue($rows, 'Veranstaltungen', 'Großveranstaltungen'))->toBe(1)
        ->and(rfValue($rows, 'Veranstaltungen', 'Bestätigte Anmeldungen'))->toBe(3)
        ->and(rfValue($rows, 'Veranstaltungen', 'Beteiligte Schulen'))->toBe(2)
        ->and(rfValue($rows, 'Veranstaltungen', 'Teilnehmende mit Anwesenheit'))->toBe(2)
        ->and(rfValue($rows, 'Veranstaltungen', 'Anwesenheiten (Teilnahmen je Tag)'))->toBe(2)
        ->and(rfValue($rows, 'Mitglieder', 'Mitglieder am 31.12.'))->toBe(2)
        ->and(rfValue($rows, 'Mitglieder', 'Neue Mitglieder im Jahr'))->toBe(1)
        ->and(rfValue($rows, 'Mitglieder', 'Ausgetreten im Jahr'))->toBe(1);

    expect(rfValue(YearReport::rows($year - 1), 'Seminare', 'Durchgeführte Seminare'))->toBe(1);
});

it('shows the report to the board only, with the year switch and a csv export', function () {
    $this->get(route('board.reports'))->assertRedirect(route('login'));

    foreach ([null, RoleKey::Coordination, RoleKey::AdministrationStaff, RoleKey::Administration, RoleKey::EducationCoordination] as $role) {
        $this->actingAs(rfUser($role))->get(route('board.reports'))->assertForbidden();
        $this->actingAs(rfUser($role))->get(route('board.reports.export'))->assertForbidden();
    }

    $board = rfUser(RoleKey::BoardMember);
    $year = now()->year;

    $this->actingAs($board)->get(route('board.reports'))->assertOk()->assertSee('Jahresbericht '.$year)->assertSee('Durchgeführte Seminare')->assertSee('Mitglieder am 31.12.');
    $this->actingAs($board)->get(route('board.reports', ['jahr' => 1999]))->assertOk()->assertSee('Jahresbericht '.$year);

    $body = $this->actingAs($board)->get(route('board.reports.export'))->assertOk()->streamedContent();
    expect($body)->toContain('Jahr;Bereich;Kennzahl;Wert')->toContain('Durchgeführte Seminare')->toContain((string) $year);
});

it('lists the report in the board navigation', function () {
    $board = rfUser(RoleKey::BoardMember);

    expect(array_column(PortalNavigation::pages(PortalAreas::Vorstand, $board), 'label'))->toContain('Berichte');
});

// ---------- Schulen: Erinnerung, Sperren, Übersicht ----------

it('reminds active school accounts once before the registration deadline', function () {
    $account = rfSchoolAccount();
    $pending = rfSchoolAccount('Schule Zwei', 'zwei@schule.example');
    $pending->user->forceFill(['status' => UserStatus::PendingVerification])->save();
    $soon = app(EventAdministration::class)->create(['title' => 'Bald', 'status' => 'open', 'registration_deadline' => now()->addDays(2)->format('Y-m-d H:i:s')], rfUser(RoleKey::Coordination));
    app(EventAdministration::class)->create(['title' => 'Später', 'status' => 'open', 'registration_deadline' => now()->addDays(20)->format('Y-m-d H:i:s')], rfUser(RoleKey::Coordination));
    app(EventAdministration::class)->create(['title' => 'Ohne Frist', 'status' => 'open'], rfUser(RoleKey::Coordination));
    app(EventAdministration::class)->create(['title' => 'Vorbei', 'status' => 'open', 'registration_deadline' => now()->subDay()->format('Y-m-d H:i:s')], rfUser(RoleKey::Coordination));
    $soon->registrations()->create(['partner_id' => $account->partner_id, 'name' => 'A', 'school' => $account->partner->name, 'class_name' => '9', 'birth_date' => '2010-01-01', 'email' => 'a@example.org', 'ticket_code' => 'REM1234567', 'verified_at' => now()]);

    $this->artisan('events:remind-schools', ['--dry-run' => true])->expectsOutputToContain('Erinnerungen an Schulen: 1')->assertSuccessful();
    Mail::assertNotSent(SchoolEventReminderMail::class);

    $this->artisan('events:remind-schools')->expectsOutputToContain('Erinnerungen an Schulen: 1')->assertSuccessful();
    Mail::assertSent(SchoolEventReminderMail::class, 1);
    Mail::assertSent(SchoolEventReminderMail::class, fn (SchoolEventReminderMail $mail): bool => $mail->hasTo($account->user->email) && $mail->ownCount === 1 && $mail->event->is($soon) && str_contains($mail->render(), 'Von Ihrer Schule sind bisher 1'));

    $this->artisan('events:remind-schools')->expectsOutputToContain('Erinnerungen an Schulen: 0')->assertSuccessful();
});

it('lets the coordination block and unblock a school account', function () {
    $account = rfSchoolAccount();
    $coordination = rfUser(RoleKey::Coordination);
    $pending = rfSchoolAccount('Schule Zwei', 'zwei@schule.example');
    $pending->user->forceFill(['status' => UserStatus::PendingVerification])->save();

    $this->actingAs($coordination)->get(route('partners.show', $account->partner))->assertOk()->assertSee('Sperren');

    $this->actingAs($coordination)->post(route('partners.accounts.toggle', [$account->partner, $account]))->assertSessionHas('success');
    expect($account->user->fresh()->status)->toBe(UserStatus::Disabled);

    $this->actingAs($coordination)->get(route('partners.show', $account->partner))->assertSee('Entsperren');
    $this->actingAs($coordination)->post(route('partners.accounts.toggle', [$account->partner, $account]))->assertSessionHas('success');
    expect($account->user->fresh()->status)->toBe(UserStatus::Active);

    $this->actingAs($coordination)->post(route('partners.accounts.toggle', [$pending->partner, $pending]))->assertSessionHasErrors('account');
    $this->actingAs($coordination)->post(route('partners.accounts.toggle', [$account->partner, $pending]))->assertNotFound();
    $this->actingAs(rfUser(RoleKey::Member))->post(route('partners.accounts.toggle', [$account->partner, $account]))->assertForbidden();
});

it('shows the registrations of an event by school', function () {
    $coordination = rfUser(RoleKey::Coordination);
    $event = app(EventAdministration::class)->create(['title' => 'Demokratietag', 'status' => 'open', 'capacity' => 2, 'waitlist_enabled' => true], $coordination);

    foreach ([['Schule Eins', false], ['Schule Eins', false], ['Schule Zwei', true]] as $i => [$school, $waiting]) {
        $event->registrations()->create(['name' => 'S'.$i, 'school' => $school, 'class_name' => '9', 'birth_date' => '2010-01-01', 'email' => 's'.$i.'@example.org', 'ticket_code' => 'SCH'.$i.'123456', 'verified_at' => now(), 'waitlisted' => $waiting]);
    }

    $response = $this->actingAs($coordination)->get(route('events.admin.show', $event))->assertOk()->assertSee('Anmeldungen nach Schule');
    $rows = collect($response->viewData('bySchool'))->mapWithKeys(fn ($row): array => [$row->school => [(int) $row->total, (int) $row->waiting]])->all();

    expect($rows)->toBe(['Schule Eins' => [2, 0], 'Schule Zwei' => [1, 1]]);
});
