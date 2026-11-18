<?php

use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\Coordinations\Models\Coordination;
use App\Modules\Partners\Models\Partner;
use App\Modules\Partners\Models\PartnerContact;
use App\Modules\Partners\Services\SchoolAccounts;
use App\Modules\People\Models\Person;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Services\SeminarWorkflow;
use App\Modules\Seminars\Support\SeminarNumbers;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

function smUser(?RoleKey $role = null): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

/** Schulkonto mit Schule und Koordination. */
function smSchool(string $name = 'Schule Eins', string $email = 'eins@schule.example'): array
{
    $coordination = Coordination::query()->firstOrCreate(['name' => 'Neukölln']);
    $partner = Partner::factory()->create(['name' => $name, 'coordination_id' => $coordination->id]);
    $contact = PartnerContact::query()->create(['partner_id' => $partner->id, 'name' => 'Frau Lehrkraft', 'email' => $email]);
    $account = app(SchoolAccounts::class)->invite($partner, $contact, smUser(RoleKey::Coordination));
    $account->user->forceFill(['status' => UserStatus::Active])->save();

    return [$account->fresh(['user', 'partner']), $partner];
}

// ---------- Nummern und Status ----------

it('numbers seminars per year without gaps', function () {
    expect(SeminarNumbers::next(2027))->toBe('S-2027-0001')->and(SeminarNumbers::next(2027))->toBe('S-2027-0002')->and(SeminarNumbers::next(2028))->toBe('S-2028-0001');
});

it('derives the school year from a date', function () {
    expect(Seminar::schoolYearOf(new DateTimeImmutable('2026-09-01')))->toBe('2026/27')
        ->and(Seminar::schoolYearOf(new DateTimeImmutable('2027-03-15')))->toBe('2026/27')
        ->and(Seminar::schoolYearOf(new DateTimeImmutable('2027-08-01')))->toBe('2027/28');
});

it('describes every status and puts the ended ones outside the flow', function () {
    foreach (SeminarStatus::cases() as $status) {
        expect($status->label())->not->toBe('')->and($status->tone())->toBeIn(['neutral', 'success', 'warning', 'error']);
    }

    expect(SeminarStatus::Rejected->isEnded())->toBeTrue()->and(SeminarStatus::Cancelled->isEnded())->toBeTrue()->and(SeminarStatus::Held->isEnded())->toBeFalse()
        ->and(SeminarStatus::Rejected->position())->toBeNull()->and(SeminarStatus::Completed->position())->toBe(11);
});

it('knows the allowed transitions and when a reason is needed', function () {
    expect(SeminarWorkflow::exists(SeminarStatus::Request, SeminarStatus::InReview))->toBeTrue()
        ->and(SeminarWorkflow::exists(SeminarStatus::Request, SeminarStatus::Completed))->toBeFalse()
        ->and(SeminarWorkflow::exists(SeminarStatus::Completed, SeminarStatus::Reported))->toBeTrue()
        ->and(SeminarWorkflow::needsReason(SeminarStatus::Request, SeminarStatus::InReview))->toBeFalse()
        ->and(SeminarWorkflow::needsReason(SeminarStatus::Request, SeminarStatus::Rejected))->toBeTrue()
        ->and(SeminarWorkflow::needsReason(SeminarStatus::Prepared, SeminarStatus::Planned))->toBeTrue()
        ->and(SeminarWorkflow::needsReason(SeminarStatus::Completed, SeminarStatus::Reported))->toBeTrue()
        ->and(SeminarWorkflow::needsReason(SeminarStatus::Rejected, SeminarStatus::InReview))->toBeTrue()
        ->and(SeminarWorkflow::needsReason(SeminarStatus::Planned, SeminarStatus::Tendered))->toBeFalse();

    // jeder Endzustand ist von irgendwo erreichbar und jedes Ziel ein gültiger Status
    foreach (SeminarStatus::cases() as $from) {
        foreach (SeminarWorkflow::targets($from) as $to) {
            expect($to)->toBeInstanceOf(SeminarStatus::class)->and($to)->not->toBe($from);
        }
    }
});

// ---------- Anfrage ----------

it('creates a request for a school with number, coordination and history', function () {
    [$account, $partner] = smSchool();

    $seminar = app(SeminarWorkflow::class)->request($partner, ['title' => ' Demokratietag ', 'participants' => 30, 'contact_email' => 'Lehrkraft@Schule.EXAMPLE'], $account->user);

    expect($seminar->status)->toBe(SeminarStatus::Request)->and($seminar->title)->toBe('Demokratietag')->and($seminar->number)->toStartWith('S-'.now()->year.'-')
        ->and($seminar->coordination_id)->toBe($partner->coordination_id)->and($seminar->contact_email)->toBe('lehrkraft@schule.example')
        ->and($seminar->events()->count())->toBe(1)->and($seminar->events()->first()->describe())->toBe('Anfrage gestellt')
        ->and(AuditEntry::query()->where('event', 'seminars.requested')->count())->toBe(1);
});

it('lets a school account ask for a seminar and see only its own', function () {
    [$account, $partner] = smSchool();
    [, $other] = smSchool('Schule Zwei', 'zwei@schule.example');
    $foreign = Seminar::factory()->create(['partner_id' => $other->id, 'title' => 'Fremdes Seminar']);

    $this->actingAs($account->user)->get(route('school.seminars.create'))->assertOk()->assertSee('Seminar anfragen');
    $this->post(route('school.seminars.store'), ['title' => '', 'contact_name' => '', 'contact_email' => ''])->assertSessionHasErrors(['title', 'contact_name', 'contact_email']);
    $this->post(route('school.seminars.store'), ['title' => 'Medienkompetenz', 'participants' => 24, 'contact_name' => 'Frau Lehrkraft', 'contact_email' => 'eins@schule.example'])->assertRedirect()->assertSessionHas('success');

    $own = Seminar::query()->where('partner_id', $partner->id)->firstOrFail();

    $this->get(route('school.seminars.index'))->assertOk()->assertSee('Medienkompetenz')->assertDontSee('Fremdes Seminar');
    $this->get(route('school.seminars.show', $own))->assertOk()->assertSee('Anfrage');
    $this->get(route('school.seminars.show', $foreign))->assertNotFound();
});

it('keeps the school pages away from everybody else', function () {
    $this->get(route('school.seminars.index'))->assertRedirect(route('login'));

    foreach ([RoleKey::Team, RoleKey::Member, RoleKey::Coordination, RoleKey::AdministrationStaff] as $role) {
        $this->actingAs(smUser($role))->get(route('school.seminars.index'))->assertForbidden();
    }
});

// ---------- Geschäftsstelle ----------

it('shows the office all seminars, filtered', function () {
    $mitte = Coordination::query()->create(['name' => 'Mitte']);
    $partner = Partner::factory()->create(['name' => 'Goethe-Schule', 'coordination_id' => $mitte->id]);
    Seminar::factory()->create(['partner_id' => $partner->id, 'coordination_id' => $mitte->id, 'title' => 'Alpha-Seminar']);
    Seminar::factory()->status(SeminarStatus::InReview)->create(['title' => 'Beta-Seminar']);

    $this->get(route('seminars.office.index'))->assertRedirect(route('login'));
    $this->actingAs(smUser(RoleKey::Coordination))->get(route('seminars.office.index'))->assertForbidden();

    $this->actingAs(smUser(RoleKey::AdministrationStaff));
    $this->get(route('seminars.office.index'))->assertOk()->assertSee('Alpha-Seminar')->assertSee('Beta-Seminar');
    $this->get(route('seminars.office.index', ['status' => 'in_review']))->assertOk()->assertSee('Beta-Seminar')->assertDontSee('Alpha-Seminar');
    $this->get(route('seminars.office.index', ['coordination' => $mitte->id]))->assertOk()->assertSee('Alpha-Seminar')->assertDontSee('Beta-Seminar');
    $this->get(route('seminars.office.index', ['q' => 'Goethe']))->assertOk()->assertSee('Alpha-Seminar')->assertDontSee('Beta-Seminar');
});

it('lets the office start the review and reject with a reason, and writes history and log', function () {
    $staff = smUser(RoleKey::AdministrationStaff);
    $seminar = Seminar::factory()->create();

    $this->actingAs($staff)->get(route('seminars.office.show', $seminar))->assertOk()->assertSee('Prüfung beginnen')->assertSee('Anfrage ablehnen');

    $this->post(route('seminars.office.status', $seminar), ['to' => 'in_review'])->assertRedirect(route('seminars.office.show', $seminar))->assertSessionHas('success');
    expect($seminar->fresh()->status)->toBe(SeminarStatus::InReview);

    $this->post(route('seminars.office.status', $seminar), ['to' => 'rejected'])->assertSessionHasErrors('comment');
    expect($seminar->fresh()->status)->toBe(SeminarStatus::InReview);

    $this->post(route('seminars.office.status', $seminar), ['to' => 'rejected', 'comment' => 'Zeitraum liegt außerhalb unseres Angebots'])->assertSessionHas('success');

    expect($seminar->fresh()->status)->toBe(SeminarStatus::Rejected)
        ->and($seminar->events()->where('type', 'status')->count())->toBe(2)
        ->and($seminar->events()->first()->comment)->toBe('Zeitraum liegt außerhalb unseres Angebots')
        ->and(AuditEntry::query()->where('event', 'seminars.status_changed')->count())->toBe(2);

    $this->get(route('seminars.office.show', $seminar))->assertSee('Zeitraum liegt außerhalb unseres Angebots')->assertSee('In Prüfung → Abgelehnt');
});

it('refuses steps that are not allowed or not permitted', function () {
    $seminar = Seminar::factory()->create();
    $workflow = app(SeminarWorkflow::class);

    expect(fn () => $workflow->transition($seminar, SeminarStatus::Completed, smUser(RoleKey::AdministrationStaff)))->toThrow(DomainException::class, 'geht es nicht direkt');
    expect(fn () => $workflow->transition($seminar, SeminarStatus::InReview, smUser(RoleKey::Coordination)))->toThrow(DomainException::class, 'Berechtigung');
    expect(fn () => $workflow->transition($seminar, SeminarStatus::InReview, smUser(RoleKey::Member)))->toThrow(DomainException::class, 'Berechtigung');

    // die einfache Statusadresse lässt nur einfache Schritte zu
    $this->actingAs(smUser(RoleKey::AdministrationStaff))->post(route('seminars.office.status', $seminar), ['to' => 'quote_sent'])->assertSessionHasErrors('to');
    $this->post(route('seminars.office.status', $seminar), ['to' => 'unbekannt'])->assertSessionHasErrors('to');
    expect($seminar->fresh()->status)->toBe(SeminarStatus::Request);
});

it('lets only the board reopen a completed seminar and only with a reason', function () {
    $seminar = Seminar::factory()->status(SeminarStatus::Completed)->create();
    $workflow = app(SeminarWorkflow::class);

    expect(fn () => $workflow->transition($seminar, SeminarStatus::Reported, smUser(RoleKey::AdministrationStaff), 'Fehler im Bericht'))->toThrow(DomainException::class, 'Berechtigung');
    expect(fn () => $workflow->transition($seminar, SeminarStatus::Reported, smUser(RoleKey::BoardMember)))->toThrow(DomainException::class, 'Begründung');

    $workflow->transition($seminar, SeminarStatus::Reported, smUser(RoleKey::BoardMember), 'Fehler im Bericht');

    expect($seminar->fresh()->status)->toBe(SeminarStatus::Reported);
});

it('does not delete a school that has seminars', function () {
    $partner = Partner::factory()->create();
    Seminar::factory()->create(['partner_id' => $partner->id]);

    $this->actingAs(smUser(RoleKey::Coordination))->delete(route('partners.destroy', $partner))->assertRedirect(route('partners.show', $partner))->assertSessionHas('warning');

    expect(Partner::query()->whereKey($partner->id)->exists())->toBeTrue();
});
