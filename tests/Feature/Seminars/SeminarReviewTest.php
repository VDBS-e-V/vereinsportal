<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Contracts\Models\ContractTemplate;
use App\Modules\Contracts\Models\SchoolContract;
use App\Modules\Partners\Models\Partner;
use App\Modules\People\Models\Person;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Services\SeminarAdministration;
use App\Modules\Seminars\Services\SeminarWorkflow;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

function srUser(RoleKey $role): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email, 'email_verified_at' => now()]);
    RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);

    return $user;
}

function srQuote(Seminar $seminar): void
{
    $seminar->quotes()->create(['partner_id' => $seminar->partner_id, 'version' => 1, 'positions' => [], 'total_cents' => 120000, 'created_by_user_id' => srUser(RoleKey::AdministrationStaff)->id]);
}

it('lets only the office see the seminar list and filters by area and status', function () {
    $area = Partner::factory()->create(['name' => 'Schule Nord', 'coordination_area' => 'Neukölln']);
    $other = Partner::factory()->create(['name' => 'Schule Süd', 'coordination_area' => 'Mitte']);
    Seminar::factory()->create(['title' => 'Tage Nord', 'partner_id' => $area->id]);
    Seminar::factory()->create(['title' => 'Tage Süd', 'partner_id' => $other->id, 'status' => SeminarStatus::InReview]);

    $this->actingAs(srUser(RoleKey::Coordination))->get(route('seminar-office.index'))->assertForbidden();

    $staff = srUser(RoleKey::AdministrationStaff);
    $this->actingAs($staff)->get(route('seminar-office.index'))->assertOk()->assertSee('Tage Nord')->assertSee('Tage Süd');
    $this->actingAs($staff)->get(route('seminar-office.index', ['area' => 'Neukölln']))->assertSee('Tage Nord')->assertDontSee('Tage Süd');
    $this->actingAs($staff)->get(route('seminar-office.index', ['status' => 'in_review']))->assertSee('Tage Süd')->assertDontSee('Tage Nord');
});

it('needs an internal quote approval from the board or coordination before the quote goes to the school', function () {
    $seminar = Seminar::factory()->create(['status' => SeminarStatus::InReview]);
    srQuote($seminar);
    $workflow = app(SeminarWorkflow::class);
    $staff = srUser(RoleKey::AdministrationStaff);

    expect(fn () => $workflow->change($seminar, SeminarStatus::QuoteSent, $staff))->toThrow(DomainException::class, 'intern freigegeben');
    expect(fn () => $workflow->approveQuote($seminar, $staff))->toThrow(DomainException::class);

    $workflow->approveQuote($seminar->fresh(), srUser(RoleKey::Coordination));
    $workflow->change($seminar->fresh(), SeminarStatus::QuoteSent, $staff);

    expect($seminar->fresh()->status)->toBe(SeminarStatus::QuoteSent);
});

it('invalidates the approval when the quote changes afterwards', function () {
    $seminar = Seminar::factory()->create(['status' => SeminarStatus::InReview]);
    srQuote($seminar);
    $workflow = app(SeminarWorkflow::class);
    $workflow->approveQuote($seminar, srUser(RoleKey::BoardMember));
    expect($workflow->quoteApproved($seminar->fresh()))->toBeTrue();

    $this->travel(2)->seconds();
    $seminar->quotes()->first()->forceFill(['total_cents' => 150000])->save();

    expect($workflow->quoteApproved($seminar->fresh()))->toBeFalse();
});

it('lets only the office decide on review, release and rejection', function () {
    $seminar = Seminar::factory()->create();
    $workflow = app(SeminarWorkflow::class);

    expect(fn () => $workflow->change($seminar, SeminarStatus::InReview, srUser(RoleKey::Coordination)))->toThrow(DomainException::class, 'Geschäftsstelle');

    $workflow->change($seminar, SeminarStatus::InReview, srUser(RoleKey::AdministrationStaff));
    expect($seminar->fresh()->status)->toBe(SeminarStatus::InReview);
});

it('creates the school contract from the template, records the signature and keeps cancelled ones apart', function () {
    $staff = srUser(RoleKey::AdministrationStaff);
    $partner = Partner::factory()->create(['name' => 'Schule Nord', 'city' => 'Berlin']);
    $seminar = Seminar::factory()->create(['partner_id' => $partner->id, 'title' => 'SV-Tage Herbst', 'status' => SeminarStatus::QuoteSent]);
    app(SeminarAdministration::class)->addSession($seminar, ['date' => now()->addMonth()->toDateString(), 'start' => '09:00', 'end' => '15:00'], $staff);
    srQuote($seminar);

    $this->actingAs(srUser(RoleKey::Coordination))->get(route('school-contracts.show', $seminar))->assertForbidden();

    $this->actingAs($staff)->get(route('school-contracts.show', $seminar))->assertOk()->assertSee('Noch kein Vertrag');
    $this->actingAs($staff)->post(route('school-contracts.store', $seminar))->assertSessionHasNoErrors();

    $contract = SchoolContract::query()->where('seminar_id', $seminar->id)->firstOrFail();
    expect($contract->status)->toBe('draft')->and($contract->body)->toContain('Schule Nord')->and($contract->body)->toContain('SV-Tage Herbst')->and($contract->body)->toContain('1.200,00');

    $this->actingAs($staff)->post(route('school-contracts.store', $seminar))->assertSessionHas('warning');
    $this->actingAs($staff)->get(route('school-contracts.print', [$seminar, $contract]))->assertOk()->assertSee('Schule Nord');

    $this->actingAs($staff)->post(route('school-contracts.sign', [$seminar, $contract]), ['signed_on' => now()->addDay()->toDateString()])->assertSessionHasErrors('signed_on');
    $this->actingAs($staff)->post(route('school-contracts.sign', [$seminar, $contract]), ['signed_on' => now()->toDateString()])->assertSessionHasNoErrors();
    expect($contract->fresh()->status)->toBe('signed')->and($seminar->fresh()->school_contract_signed_at->toDateString())->toBe(now()->toDateString());

    $this->actingAs($staff)->post(route('school-contracts.cancel', [$seminar, $contract]));
    expect($contract->fresh()->status)->toBe('cancelled');
    $this->actingAs($staff)->post(route('school-contracts.store', $seminar))->assertSessionHasNoErrors();
    expect(SchoolContract::query()->where('seminar_id', $seminar->id)->count())->toBe(2);
});

it('versions the school contract template and rejects unknown placeholders', function () {
    $staff = srUser(RoleKey::AdministrationStaff);

    $this->actingAs($staff)->get(route('school-contracts.template'))->assertOk()->assertSee('{schule_name}');
    $this->actingAs($staff)->put(route('school-contracts.template.update'), ['title' => 'Vertrag', 'body' => '<p>Mit {unbekannt}</p>'])->assertSessionHasErrors('body');
    $this->actingAs($staff)->put(route('school-contracts.template.update'), ['title' => 'Vertrag neu', 'body' => '<p>Schule {schule_name}</p>'])->assertSessionHasNoErrors();

    expect(ContractTemplate::query()->where('key', 'school_agreement')->count())->toBe(2)
        ->and(ContractTemplate::query()->where('key', 'school_agreement')->where('active', true)->value('version'))->toBe(2);
});

it('shows the review box on the seminar and lets the board approve the quote from there', function () {
    $seminar = Seminar::factory()->create(['status' => SeminarStatus::InReview]);
    srQuote($seminar);

    $this->actingAs(srUser(RoleKey::AdministrationStaff))->post(route('seminars.quote.approve', $seminar))->assertForbidden();

    $board = srUser(RoleKey::BoardMember);
    $this->actingAs($board)->get(route('seminars.show', $seminar))->assertOk()->assertSee('Prüfung und Freigabe')->assertSee('Kostenvoranschlag intern freigeben');
    $this->actingAs($board)->post(route('seminars.quote.approve', $seminar))->assertSessionHas('success');
    expect($seminar->fresh()->quote_approved_at)->not->toBeNull();
});
