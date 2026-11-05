<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Contacts\Models\TeamContact;
use App\Modules\Partners\Models\Partner;
use App\Modules\People\Models\Person;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Enums\SeminarType;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use App\Modules\Seminars\Models\SeminarClosure;
use App\Modules\Seminars\Models\SeminarDocument;
use App\Modules\Seminars\Services\GeneratedDocuments;
use App\Modules\Seminars\Services\SeminarAdministration;
use App\Modules\Seminars\Services\SeminarClosures;
use App\Support\YearReport;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Storage::fake('local');
});

function ssUser(?RoleKey $role = RoleKey::Coordination): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

/** Ein abgeschlossenes Seminar. */
function ssClosed(array $attributes = [], string $day = '2026-03-10', ?User $by = null): Seminar
{
    $seminar = Seminar::factory()->create($attributes + ['status' => SeminarStatus::Done, 'expected_participants' => 30]);
    $by ??= ssUser();
    app(SeminarAdministration::class)->addSession($seminar, ['date' => $day, 'start' => '09:00', 'end' => '15:00'], $by);
    app(SeminarClosures::class)->close($seminar->fresh(), [], ['quote' => 'x', 'invoice' => 'x', 'team' => 'x', 'evaluation' => 'x', 'payments' => 'x'], $by);

    return $seminar->fresh();
}

it('lists closed seminars with their figures and sums and keeps others out', function () {
    $a = ssClosed(['title' => 'Erstes Seminar'], '2026-03-10');
    $b = ssClosed(['title' => 'Zweites Seminar'], '2027-04-12');
    Seminar::factory()->create(['title' => 'Noch offen', 'status' => SeminarStatus::Done]);

    $this->actingAs(ssUser())->get(route('seminar-stats.index'))->assertOk()->assertSee('Erstes Seminar')->assertSee('Zweites Seminar')->assertDontSee('Noch offen')
        ->assertSee('Summe (2 Seminare)');
});

it('filters by several years, services and schools at once', function () {
    $schoolA = Partner::factory()->create(['name' => 'Schule Alpha']);
    $schoolB = Partner::factory()->create(['name' => 'Schule Beta']);
    ssClosed(['title' => 'Alt Alpha', 'partner_id' => $schoolA->id, 'type' => SeminarType::SvDays], '2025-05-05');
    ssClosed(['title' => 'Neu Beta', 'partner_id' => $schoolB->id, 'type' => SeminarType::SvDays], '2026-05-05');
    ssClosed(['title' => 'Neu Alpha', 'partner_id' => $schoolA->id, 'type' => SeminarType::Coaching], '2026-06-06');
    $this->actingAs(ssUser());

    $this->get(route('seminar-stats.index', ['jahr' => ['2025', '2026']]))->assertSee('Alt Alpha')->assertSee('Neu Beta')->assertSee('Neu Alpha');
    $this->get(route('seminar-stats.index', ['jahr' => ['2026']]))->assertDontSee('Alt Alpha')->assertSee('Neu Beta')->assertSee('Neu Alpha');
    $this->get(route('seminar-stats.index', ['schule' => [$schoolA->id]]))->assertSee('Alt Alpha')->assertSee('Neu Alpha')->assertDontSee('Neu Beta');
    $this->get(route('seminar-stats.index', ['leistung' => ['coaching'], 'schule' => [$schoolA->id, $schoolB->id]]))->assertSee('Neu Alpha')->assertDontSee('Alt Alpha')->assertDontSee('Neu Beta');
});

it('ignores a closure that was reopened', function () {
    $seminar = ssClosed(['title' => 'Wieder offen']);
    app(SeminarClosures::class)->reopen($seminar, 'Fehler', ssUser(RoleKey::BoardMember));

    $this->actingAs(ssUser())->get(route('seminar-stats.index'))->assertOk()->assertDontSee('Wieder offen');
});

it('exports the statistics as csv with formula protection and a sum row', function () {
    ssClosed(['title' => '=HYPERLINK("x")'], '2026-03-10');
    ssClosed(['title' => 'Normal'], '2026-04-10');

    $response = $this->actingAs(ssUser())->get(route('seminar-stats.export'))->assertOk();
    $csv = $response->streamedContent();

    expect($response->headers->get('content-type'))->toContain('text/csv')->and($csv)->toContain("'=HYPERLINK")->and($csv)->toContain('Normal')->and($csv)->toContain('Summe;"2 Seminare"');
});

it('keeps the statistics for those who may see seminars', function () {
    $this->get(route('seminar-stats.index'))->assertRedirect(route('login'));

    foreach ([RoleKey::Team, RoleKey::School, RoleKey::Member, null] as $role) {
        $this->actingAs(ssUser($role))->get(route('seminar-stats.index'))->assertForbidden();
    }

    foreach ([RoleKey::Coordination, RoleKey::AdministrationStaff, RoleKey::BoardMember, RoleKey::EducationCoordination] as $role) {
        $this->actingAs(ssUser($role))->get(route('seminar-stats.index'))->assertOk();
    }
});

it('shows documents that expire soon only to those who manage documents', function () {
    $seminar = ssClosed();
    SeminarDocument::query()->where('seminar_id', $seminar->id)->update(['retention_until' => now()->addDays(10)->toDateString()]);
    $document = SeminarDocument::query()->where('seminar_id', $seminar->id)->first();

    $this->actingAs(ssUser())->get(route('seminar-stats.index'))->assertSee('Aufbewahrungsfrist bald abläuft')->assertSee($document->title);
});

it('adds the closed seminars and their hours to the year report', function () {
    ssClosed([], '2026-03-10');
    ssClosed([], '2026-04-10');

    $rows = collect(YearReport::rows((int) now()->format('Y')));

    expect($rows->firstWhere('label', 'Abgeschlossene Seminare (mit Abschluss im Portal)')['value'])->toBe(2)
        ->and($rows->firstWhere('label', 'Seminarstunden laut Abschluss')['value'])->toBe('12');
});

it('stores the year and the figures in the snapshot', function () {
    $seminar = ssClosed([], '2026-03-10');

    expect(SeminarClosure::query()->first()->snapshot)->toMatchArray(['year' => '2026', 'first_day' => '10.03.2026', 'sessions' => 1]);
});

it('generates the schedule and the team overview as html and keeps contact data to those who agreed', function () {
    $seminar = Seminar::factory()->create(['status' => SeminarStatus::Done, 'location_notes' => 'Raum 12']);
    $by = ssUser();
    app(SeminarAdministration::class)->addSession($seminar, ['date' => '2026-03-10', 'start' => '09:00', 'end' => '15:00', 'location' => 'Aula'], $by);
    $with = TeamContact::query()->create(['email' => 'mit@example.org']);
    $without = TeamContact::query()->create(['email' => 'ohne@example.org']);
    SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'contact_id' => $with->id, 'status' => 'confirmed', 'confirmed_at' => now(), 'guest_name' => 'Mia Mit', 'guest_phone' => '030 5', 'guest_consent_contact' => true]);
    SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'contact_id' => $without->id, 'status' => 'confirmed', 'confirmed_at' => now(), 'guest_name' => 'Otto Ohne', 'guest_phone' => '030 6', 'guest_consent_contact' => false]);
    $this->actingAs($by);

    $this->post(route('seminars.documents.generate', $seminar), ['type' => 'schedule', 'format' => 'html'])->assertSessionHas('success');
    $this->post(route('seminars.documents.generate', $seminar), ['type' => 'team', 'format' => 'html'])->assertSessionHas('success');

    $schedule = SeminarDocument::query()->where('title', 'Terminübersicht')->first();
    $team = SeminarDocument::query()->where('title', 'Teamübersicht')->first();
    $scheduleHtml = Storage::disk('local')->get($schedule->path);
    $teamHtml = Storage::disk('local')->get($team->path);

    expect($schedule->is_snapshot)->toBeTrue()->and($schedule->mime)->toBe('text/html')->and($scheduleHtml)->toContain('Aula')->toContain('10.03.2026')->toContain('Raum 12')
        ->and($teamHtml)->toContain('Mia Mit')->toContain('mit@example.org')->toContain('Otto Ohne')->not->toContain('ohne@example.org')->not->toContain('030 6');
});

it('refuses the pdf when the package is missing and rejects unknown types', function () {
    $seminar = Seminar::factory()->create(['status' => SeminarStatus::Done]);
    $this->actingAs(ssUser());

    if (! GeneratedDocuments::pdfAvailable()) {
        $this->post(route('seminars.documents.generate', $seminar), ['type' => 'schedule', 'format' => 'pdf'])->assertSessionHasErrors('documents');
        expect(SeminarDocument::query()->count())->toBe(0);
    } else {
        $this->post(route('seminars.documents.generate', $seminar), ['type' => 'schedule', 'format' => 'pdf'])->assertSessionHas('success');
        expect(SeminarDocument::query()->first()->mime)->toBe('application/pdf');
    }

    $this->post(route('seminars.documents.generate', $seminar), ['type' => 'geheim', 'format' => 'html'])->assertSessionHasErrors('type');
    $this->post(route('seminars.documents.generate', $seminar), ['type' => 'team', 'format' => 'exe'])->assertSessionHasErrors('format');
});

it('creates the schedule and the team overview automatically at the closure and protects them afterwards', function () {
    $coordination = ssUser();
    $seminar = ssClosed([], '2026-03-10', $coordination);

    $titles = SeminarDocument::query()->where('seminar_id', $seminar->id)->pluck('title')->all();
    expect($titles)->toContain('Terminübersicht')->toContain('Teamübersicht');

    $document = SeminarDocument::query()->where('title', 'Terminübersicht')->first();
    $this->actingAs($coordination)->post(route('seminars.documents.destroy', $seminar), ['ids' => [$document->id]])->assertSessionHasErrors('documents');
    expect(SeminarDocument::query()->whereKey($document->id)->exists())->toBeTrue();
});

it('offers the pdf buttons only when the package exists and always the html buttons', function () {
    $seminar = Seminar::factory()->create(['status' => SeminarStatus::Done]);

    $response = $this->actingAs(ssUser())->get(route('seminars.show', $seminar))->assertOk()->assertSee('Terminübersicht (HTML)')->assertSee('Teamübersicht (HTML)');
    GeneratedDocuments::pdfAvailable() ? $response->assertSee('Terminübersicht (PDF)') : $response->assertDontSee('(PDF)');
});

it('shows the statistics page in the navigation of the coordination', function () {
    $this->actingAs(ssUser())->get(route('coordination.home'))->assertOk()->assertSee('Statistik');
});
