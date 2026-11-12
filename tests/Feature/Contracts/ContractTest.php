<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\Contracts\Mail\ContractScanReminderMail;
use App\Modules\Contracts\Models\ContractorProfile;
use App\Modules\Contracts\Models\SeminarContract;
use App\Modules\Contracts\Services\ContractTexts;
use App\Modules\People\Models\Person;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use App\Modules\Seminars\Models\SeminarDocument;
use App\Modules\Seminars\Models\SeminarPriceList;
use App\Modules\Seminars\Models\SeminarQuote;
use App\Modules\Seminars\Models\SeminarSession;
use App\Modules\Seminars\Services\SeminarAdministration;
use App\Modules\Seminars\Services\SeminarClosures;
use Database\Seeders\RoleSeeder;
use Dompdf\Dompdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Storage::fake('local');
});

function ctrUser(?RoleKey $role, array $person = []): User
{
    $p = Person::factory()->create($person);
    $user = User::factory()->create(['person_id' => $p->id, 'email' => $p->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

/** @return array{0: Seminar, 1: list<SeminarApplication>} */
function ctrSeminar(int $team = 2): array
{
    $seminar = Seminar::factory()->create(['status' => SeminarStatus::Scheduled, 'title' => 'SV-Tage Nord']);
    app(SeminarAdministration::class)->addSession($seminar, ['date' => now()->addMonth()->toDateString(), 'start' => '09:00', 'end' => '15:00'], ctrUser(RoleKey::Coordination));
    SeminarQuote::query()->create(['partner_id' => $seminar->partner_id, 'seminar_id' => $seminar->id, 'price_list_id' => SeminarPriceList::current()->id, 'version' => 1, 'total_cents' => 30000,
        'positions' => [['lines' => [['key' => 'honorarium', 'label' => 'Honorar', 'quantity' => 2.0, 'unit' => 'Teamende', 'unit_cents' => 15000, 'per_day' => true, 'total_cents' => 30000]]]]]);

    $applications = [];

    for ($i = 0; $i < $team; $i++) {
        $applications[] = SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'user_id' => ctrUser(RoleKey::Team, ['first_name' => 'Tina'.$i, 'last_name' => 'Team'.$i])->id, 'status' => 'confirmed']);
    }

    return [$seminar->fresh(), $applications];
}

it('creates contracts for the confirmed team with person, seminar, dates and the fee from the quote', function () {
    [$seminar] = ctrSeminar();
    $this->actingAs(ctrUser(RoleKey::Coordination));

    $this->post(route('contracts.store', $seminar), ['all' => 1])->assertRedirect()->assertSessionHas('success');

    $contract = SeminarContract::query()->orderBy('id')->firstOrFail();
    expect(SeminarContract::query()->count())->toBe(2)
        ->and($contract->number)->toStartWith('K-'.now()->format('Y').'-')
        ->and($contract->data['amount_cents'])->toBe(15000)
        ->and($contract->data['service_text'])->toContain('SV-Tage Nord')
        ->and($contract->template_version)->toBe(1);

    $this->post(route('contracts.store', $seminar), ['all' => 1])->assertSessionHas('error');

    $this->get(route('contracts.show', [$seminar, $contract]))->assertOk()
        ->assertSee('Team0, Tina0', false)->assertSee('150,00', false)->assertSee('SV-Tage Nord', false)->assertSee('contract-blank', false);
});

it('lets the fee change only with a note and prints the chosen vat box', function () {
    [$seminar] = ctrSeminar(1);
    $this->actingAs(ctrUser(RoleKey::Coordination));
    $this->post(route('contracts.store', $seminar), ['all' => 1]);
    $contract = SeminarContract::query()->firstOrFail();
    $base = ['name' => 'Team0, Tina0', 'service_text' => 'Leistung', 'amount' => '120,00', 'vat_mode' => '7'];

    $this->put(route('contracts.update', [$seminar, $contract]), $base)->assertSessionHas('error');
    $this->put(route('contracts.update', [$seminar, $contract]), $base + ['amount_note' => 'Verkürzter Einsatz'])->assertSessionHas('success');

    $fresh = $contract->fresh();
    expect($fresh->data['amount_cents'])->toBe(12000)->and($fresh->data['amount_note'])->toBe('Verkürzter Einsatz');

    $html = $this->get(route('contracts.show', [$seminar, $contract]))->assertOk()->getContent();
    expect($html)->toContain('&#9746; 7 %')->toContain('&#9744; 19 %')->toContain('&#9744; ohne Umsatzsteuer');

    $this->put(route('contracts.update', [$seminar, $contract]), $base + ['amount_note' => 'x', 'iban' => 'DE00123'])->assertSessionHas('error');
});

it('keeps tax number and iban encrypted and out of the log and the lists', function () {
    [$seminar] = ctrSeminar(1);
    $this->actingAs(ctrUser(RoleKey::Coordination));
    $this->post(route('contracts.store', $seminar), ['all' => 1]);
    $contract = SeminarContract::query()->firstOrFail();

    $this->put(route('contracts.update', [$seminar, $contract]), ['name' => 'Team0, Tina0', 'amount' => '150,00', 'tax_number' => '12/345/67890', 'iban' => 'DE89 3704 0044 0532 0130 00']);

    $raw = (string) DB::table('seminar_contracts')->value('data');
    expect($raw)->not->toContain('12/345/67890')->and($raw)->not->toContain('DE89');

    $log = AuditEntry::query()->get()->map(fn ($entry) => json_encode($entry->getAttributes()))->implode(' ');
    expect($log)->not->toContain('12/345/67890')->not->toContain('DE89');

    $page = $this->get(route('seminars.show', $seminar))->assertOk()->getContent();
    expect($page)->not->toContain('12/345/67890')->not->toContain('DE89 3704');
});

it('lets only those with the right create, read and download contracts', function () {
    [$seminar] = ctrSeminar(1);
    $this->actingAs(ctrUser(RoleKey::Coordination))->post(route('contracts.store', $seminar), ['all' => 1]);
    $contract = SeminarContract::query()->firstOrFail();

    foreach ([RoleKey::Team, RoleKey::Member, RoleKey::School, RoleKey::Administration] as $role) {
        $this->actingAs(ctrUser($role));
        $this->post(route('contracts.store', $seminar), ['all' => 1])->assertForbidden();
        $this->get(route('contracts.show', [$seminar, $contract]))->assertForbidden();
        $this->get(route('contracts.print', $seminar))->assertForbidden();
    }

    foreach ([RoleKey::AdministrationStaff, RoleKey::BoardMember] as $role) {
        $this->actingAs(ctrUser($role))->get(route('contracts.show', [$seminar, $contract]))->assertOk();
    }
});

it('versions the text: a new version keeps old contracts on version one and only the board changes it', function () {
    [$seminar] = ctrSeminar(1);
    $coordination = ctrUser(RoleKey::Coordination);
    $this->actingAs($coordination)->post(route('contracts.store', $seminar), ['all' => 1]);
    $old = SeminarContract::query()->firstOrFail();

    $this->post(route('contracts.admin.version'), ['title' => 'Neu', 'body' => '<p>Neuer Text {auftragnehmer_name}</p>'])->assertForbidden();

    $this->actingAs(ctrUser(RoleKey::BoardMember));
    $this->post(route('contracts.admin.version'), ['title' => 'Neu', 'body' => '<p>Neuer Text {tippfehler}</p>'])->assertSessionHasErrors('body');
    $this->post(route('contracts.admin.version'), ['title' => 'Neu', 'body' => '<p>Neuer Text {auftragnehmer_name}</p>'])->assertRedirect();

    expect(app(ContractTexts::class)->versions()->pluck('version')->all())->toBe([2, 1])->and($old->fresh()->template_version)->toBe(1);

    $this->actingAs($coordination)->get(route('contracts.show', [$seminar, $old]))->assertOk()->assertDontSee('Neuer Text', false)->assertSee('Weisungs- und Direktionsrecht', false);

    [$other] = ctrSeminar(1);
    $this->post(route('contracts.store', $other), ['all' => 1]);
    expect(SeminarContract::query()->orderByDesc('id')->first()->template_version)->toBe(2);

    $this->get(route('contracts.admin.index'))->assertOk()->assertSee('Version 2');
});

it('stores the signed scan as a seminar document and ticks the checklist', function () {
    [$seminar] = ctrSeminar(2);
    $coordination = ctrUser(RoleKey::Coordination);
    $this->actingAs($coordination)->post(route('contracts.store', $seminar), ['all' => 1]);
    $contracts = SeminarContract::query()->orderBy('id')->get();
    $scan = fn () => UploadedFile::fake()->createWithContent('scan.pdf', "%PDF-1.4\n%x\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n");

    expect(app(SeminarClosures::class)->checklist($seminar)['contracts']['auto'])->toBeFalse();

    $this->post(route('contracts.scan', [$seminar, $contracts[0]]), ['scan' => $scan()])->assertRedirect()->assertSessionHas('success');
    expect($contracts[0]->fresh()->status)->toBe('signed')->and(SeminarDocument::query()->where('kind', 'contract')->count())->toBe(1)
        ->and(app(SeminarClosures::class)->checklist($seminar)['contracts']['auto'])->toBeFalse();

    $this->post(route('contracts.scan', [$seminar, $contracts[1]]), ['scan' => $scan()]);
    expect(app(SeminarClosures::class)->checklist($seminar)['contracts']['auto'])->toBeTrue();

    $this->put(route('contracts.update', [$seminar, $contracts[0]]), ['amount' => '1,00', 'amount_note' => 'x'])->assertSessionHas('error');
    $this->post(route('contracts.cancel', [$seminar, $contracts[0]]))->assertSessionHas('error');
});

it('has no contract item on the checklist without contracts and prints one page per contract', function () {
    [$seminar] = ctrSeminar(3);
    expect(array_key_exists('contracts', app(SeminarClosures::class)->checklist($seminar)))->toBeFalse();

    $this->actingAs(ctrUser(RoleKey::Coordination));
    $this->post(route('contracts.store', $seminar), ['applications' => [SeminarApplication::query()->orderBy('id')->first()->id]]);
    expect(SeminarContract::query()->count())->toBe(1);

    $this->post(route('contracts.store', $seminar), ['all' => 1]);
    $html = $this->get(route('contracts.print', $seminar))->assertOk()->getContent();
    expect(substr_count($html, 'class="sheet"'))->toBe(3);

    $first = SeminarContract::query()->orderBy('id')->first();
    $this->post(route('contracts.cancel', [$seminar, $first]));
    expect(substr_count($this->get(route('contracts.print', $seminar))->getContent(), 'class="sheet"'))->toBe(2);
});

it('prefills a follow-up contract from the saved contract data of a team account', function () {
    [$seminar, $applications] = ctrSeminar(1);
    $user = $applications[0]->user;
    $this->actingAs($user);

    $this->put(route('contractor.update'), ['iban' => 'DE00 1234'])->assertSessionHas('error');
    $this->put(route('contractor.update'), ['tax_number' => '12/345/67890', 'vat_mode' => '19', 'bank_name' => 'Testbank', 'bic' => 'TESTDE00', 'iban' => 'DE89370400440532013000'])->assertSessionHas('success');

    $raw = (string) DB::table('contractor_profiles')->value('iban');
    expect($raw)->not->toContain('DE89')->and(ContractorProfile::query()->where('user_id', $user->id)->first()->iban)->toBe('DE89 3704 0044 0532 0130 00');

    $this->actingAs(ctrUser(RoleKey::Coordination))->post(route('contracts.store', $seminar), ['all' => 1]);
    $data = SeminarContract::query()->firstOrFail()->data;
    expect($data['tax_number'])->toBe('12/345/67890')->and($data['vat_mode'])->toBe('19')->and($data['bank_name'])->toBe('Testbank');

    $this->actingAs($user)->delete(route('contractor.destroy'));
    expect(ContractorProfile::query()->count())->toBe(0);
    $this->actingAs(ctrUser(RoleKey::Member))->get(route('contractor.edit'))->assertForbidden();
});

it('logs creation and signing with number and count but without names and amounts, and prunes after the retention', function () {
    [$seminar] = ctrSeminar(1);
    $this->actingAs(ctrUser(RoleKey::Coordination))->post(route('contracts.store', $seminar), ['all' => 1]);

    $log = AuditEntry::query()->where('event', 'contract.created')->firstOrFail();
    expect(json_encode($log->context))->toContain((string) $seminar->number)->not->toContain('Tina')->not->toContain('150');

    $contract = SeminarContract::query()->firstOrFail();
    expect($contract->retention_until->isFuture())->toBeTrue();

    $this->artisan('contracts:prune', ['--dry-run' => true])->assertSuccessful();
    expect(SeminarContract::query()->count())->toBe(1);

    $contract->forceFill(['retention_until' => now()->subDay()])->save();
    $this->artisan('contracts:prune')->assertSuccessful();
    expect(SeminarContract::query()->count())->toBe(0);
});

it('shows the contracts page in the navigation of the coordination and the teams contract data', function () {
    $this->actingAs(ctrUser(RoleKey::Coordination))->get(route('coordination.home'))->assertOk()->assertSee('Verträge');
    $this->actingAs(ctrUser(RoleKey::AdministrationStaff))->get(route('contracts.admin.index'))->assertOk()->assertSee('Vereinsdaten');
    $this->actingAs(ctrUser(RoleKey::Team))->get(route('team.home'))->assertOk()->assertSee('Vertragsdaten');
});

it('offers the pdf only when the package exists and explains it otherwise', function () {
    [$seminar] = ctrSeminar(1);
    $this->actingAs(ctrUser(RoleKey::Coordination))->post(route('contracts.store', $seminar), ['all' => 1]);
    $contract = SeminarContract::query()->firstOrFail();

    $page = $this->get(route('contracts.show', [$seminar, $contract]))->assertOk();

    if (class_exists(Dompdf::class)) {
        $page->assertSee('Als PDF herunterladen');
        $this->get(route('contracts.show', [$seminar, $contract, 'format' => 'pdf']))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    } else {
        $page->assertDontSee('Als PDF herunterladen');
        $this->from(route('contracts.show', [$seminar, $contract]))->get(route('contracts.show', [$seminar, $contract, 'format' => 'pdf']))->assertRedirect()->assertSessionHas('error');
    }
});

it('reminds the coordination once about missing scans three days after the last session and shows the leader rate', function () {
    Mail::fake();
    [$seminar] = ctrSeminar(2);
    $coordination = ctrUser(RoleKey::Coordination);
    $this->actingAs($coordination)->post(route('contracts.store', $seminar), ['all' => 1]);

    $this->artisan('contracts:remind')->assertSuccessful();
    Mail::assertNothingSent();

    SeminarSession::query()->where('seminar_id', $seminar->id)->update(['starts_at' => now()->subDays(6)->setTime(9, 0), 'ends_at' => now()->subDays(6)->setTime(15, 0)]);
    $this->artisan('contracts:remind')->assertSuccessful();
    Mail::assertSent(ContractScanReminderMail::class, fn ($mail) => $mail->hasTo($coordination->email) && $mail->open === 2);

    $this->artisan('contracts:remind')->assertSuccessful();
    // zwei Personen mit der Rolle Koordination (eine legt das Hilfsmittel für den Termin an): je eine Mail, kein zweiter Durchgang
    Mail::assertSentCount(2);
});

it('lets the coordination mark the seminar lead and adds the leader rate to that contract only', function () {
    [$seminar, $applications] = ctrSeminar(2);
    $quote = SeminarQuote::query()->where('seminar_id', $seminar->id)->firstOrFail();
    $positions = $quote->positions;
    $positions[0]['lines'][] = ['key' => 'leader', 'label' => 'Satz Seminarleitung', 'quantity' => 1.0, 'unit' => 'Seminarleitung', 'unit_cents' => 5000, 'per_day' => true, 'total_cents' => 5000];
    $quote->forceFill(['positions' => $positions])->save();

    $this->actingAs(ctrUser(RoleKey::Coordination));
    $this->post(route('seminars.team.lead', [$seminar, $applications[0]]))->assertRedirect()->assertSessionHas('success');
    expect($applications[0]->fresh()->is_lead)->toBeTrue();

    $this->post(route('contracts.store', $seminar), ['all' => 1]);
    $byApplication = SeminarContract::query()->get()->keyBy('application_id');

    expect($byApplication[$applications[0]->id]->data['amount_cents'])->toBe(20000)->and($byApplication[$applications[1]->id]->data['amount_cents'])->toBe(15000);

    $this->post(route('seminars.team.lead', [$seminar, $applications[0]]));
    expect($applications[0]->fresh()->is_lead)->toBeFalse();

    $declined = SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'user_id' => ctrUser(RoleKey::Team)->id, 'status' => 'declined']);
    $this->post(route('seminars.team.lead', [$seminar, $declined]))->assertSessionHas('error');
});
