<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Events\Models\Event;
use App\Modules\Membership\Models\Membership;
use App\Modules\Onboarding\Enums\OnboardingDocumentKind;
use App\Modules\Onboarding\Services\OnboardingDocuments;
use App\Modules\People\Models\Person;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Services\SeminarAdministration;
use App\Support\PortalAreas;
use App\Support\PortalNavigation;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Storage::fake('local');
});

function aoUser(?RoleKey $role = null): User
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

it('shows members their area with membership, documents, events and the contact to the board', function () {
    $member = aoUser(RoleKey::Member);
    Membership::factory()->create(['person_id' => $member->person_id, 'starts_on' => '2024-03-01']);
    app(OnboardingDocuments::class)->store(UploadedFile::fake()->create('satzung.pdf', 20, 'application/pdf'), OnboardingDocumentKind::Statute, aoUser(RoleKey::BoardMember));
    Event::query()->create(['slug' => 'tag-eins', 'title' => 'Demokratietag', 'status' => 'open']);
    Event::query()->create(['slug' => 'tag-zwei', 'title' => 'Interner Entwurf', 'status' => 'draft']);

    $this->get(route('member.home'))->assertRedirect(route('login'));

    $this->actingAs($member)->get(route('member.home'))->assertOk()
        ->assertSee('Mitglied seit 01.03.2024')->assertSee('Satzung (PDF')->assertSee('Willkommensbrief: wird vom Vorstand noch bereitgestellt')
        ->assertSee('Demokratietag')->assertDontSee('Interner Entwurf')->assertSee('Vorstand kontaktieren');

    $this->actingAs(aoUser(RoleKey::Team))->get(route('member.home'))->assertForbidden();
    $this->actingAs(aoUser())->get(route('member.home'))->assertForbidden();
});

it('delivers only the current statute and welcome letter to members', function () {
    $documents = app(OnboardingDocuments::class);
    $board = aoUser(RoleKey::BoardMember);
    $old = $documents->store(UploadedFile::fake()->create('alt.pdf', 10, 'application/pdf'), OnboardingDocumentKind::Statute, $board);
    $current = $documents->store(UploadedFile::fake()->create('neu.pdf', 10, 'application/pdf'), OnboardingDocumentKind::Statute, $board);
    $team = $documents->store(UploadedFile::fake()->create('erklaerung.pdf', 10, 'application/pdf'), OnboardingDocumentKind::Commitment, $board);
    $member = aoUser(RoleKey::Member);

    $this->actingAs($member)->get(route('member.documents.download', $current))->assertOk();
    $this->actingAs($member)->get(route('member.documents.download', $old))->assertNotFound();
    $this->actingAs($member)->get(route('member.documents.download', $team))->assertNotFound();
    $this->actingAs(aoUser(RoleKey::Team))->get(route('member.documents.download', $current))->assertForbidden();
});

it('shows the board key figures without personal data and keeps others out', function () {
    $board = aoUser(RoleKey::BoardMember);
    Membership::factory()->count(2)->create();
    Membership::factory()->create(['ends_on' => now()->addMonth()->toDateString()]);
    Membership::factory()->ended()->create();
    Membership::factory()->requested()->create();
    Seminar::factory()->create(['status' => SeminarStatus::Planning]);

    $response = $this->actingAs($board)->get(route('board.overview'))->assertOk()->assertSee('Aktive Mitglieder')->assertSee('Geplante Austritte');
    $stats = $response->viewData('stats');

    expect($stats['members'])->toBe(3)->and($stats['requested'])->toBe(1)->and($stats['leaving'])->toBe(1)->and($stats['seminarsPlanning'])->toBe(1);

    $this->actingAs(aoUser(RoleKey::Coordination))->get(route('board.overview'))->assertForbidden();
    $this->actingAs(aoUser(RoleKey::Member))->get(route('board.overview'))->assertForbidden();
});

it('shows Bildungsreferenten an overview and lets them read but not change seminars and events', function () {
    $education = aoUser(RoleKey::EducationCoordination);
    $seminar = Seminar::factory()->create(['title' => 'SV-Tage Süd', 'status' => SeminarStatus::Confirmed]);
    app(SeminarAdministration::class)->addSession($seminar, ['date' => now()->addMonth()->toDateString(), 'start' => '09:00', 'end' => '15:00'], aoUser(RoleKey::Coordination));
    Event::query()->create(['slug' => 'tag-eins', 'title' => 'Demokratietag', 'status' => 'open']);

    $this->actingAs($education)->get(route('education.overview'))->assertOk()->assertSee('SV-Tage Süd')->assertSee('Demokratietag')->assertSee('Seminare nach Stand');

    $this->actingAs($education)->get(route('seminars.index'))->assertOk();
    $this->actingAs($education)->get(route('seminars.show', $seminar))->assertOk()->assertDontSee('Bearbeiten');
    $this->actingAs($education)->get(route('events.admin.index'))->assertOk();
    $this->actingAs($education)->get(route('seminars.create'))->assertForbidden();
    $this->actingAs($education)->get(route('events.admin.create'))->assertForbidden();
    $this->actingAs($education)->get(route('partners.index'))->assertForbidden();

    $this->actingAs(aoUser(RoleKey::Coordination))->get(route('education.overview'))->assertForbidden();
});

it('lists the new areas in the navigation and links the area chips on the start page', function () {
    $education = aoUser(RoleKey::EducationCoordination);
    $member = aoUser(RoleKey::Member);
    $board = aoUser(RoleKey::BoardMember);

    expect(PortalNavigation::visibleAreas($education))->toContain(PortalAreas::Bildungsreferenten)->toContain(PortalAreas::Koordination)
        ->and(PortalNavigation::visibleAreas($member))->toBe([PortalAreas::Mitglieder])
        ->and(array_column(PortalNavigation::pages(PortalAreas::Vorstand, $board), 'label')[0])->toBe('Übersicht');

    $this->actingAs($member)->get('/')->assertSee('href="'.route('member.home').'"', false);
    $this->actingAs($board)->get('/')->assertSee('href="'.route('board.overview').'"', false);
});
