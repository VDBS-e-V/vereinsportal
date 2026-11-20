<?php

use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\Partners\Models\Partner;
use App\Modules\Partners\Models\PartnerContact;
use App\Modules\Partners\Services\SchoolAccounts;
use App\Modules\People\Models\Person;
use App\Modules\Quotes\Enums\QuoteStatus;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Storage::fake('local');
});

function rlUser(?RoleKey $role = null): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

/** Seminar mit gesendetem Kostenvoranschlag und Schulkonto. */
function rlSeminar(string $name = 'Schule Eins', string $email = 'eins@schule.example'): array
{
    $partner = Partner::factory()->create(['name' => $name]);
    $contact = PartnerContact::query()->create(['partner_id' => $partner->id, 'name' => 'Frau Lehrkraft', 'email' => $email]);
    $account = app(SchoolAccounts::class)->invite($partner, $contact, rlUser(RoleKey::Coordination));
    $account->user->forceFill(['status' => UserStatus::Active])->save();
    $seminar = Seminar::factory()->status(SeminarStatus::QuoteSent)->create(['partner_id' => $partner->id]);
    $seminar->quotes()->create(['version' => 1, 'status' => QuoteStatus::Sent, 'total_cents' => 50000, 'sent_at' => now()]);

    return [$seminar, $account->user->fresh()];
}

function rlPdf(string $name = 'vertrag.pdf'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n");
}

it('stores the signed school contract from the office with its date', function () {
    [$seminar] = rlSeminar();

    $this->actingAs(rlUser(RoleKey::Coordination))->post(route('seminars.office.contract', $seminar), ['file' => rlPdf(), 'signed_on' => '2027-02-01'])->assertForbidden();

    $this->actingAs(rlUser(RoleKey::AdministrationStaff));
    $this->post(route('seminars.office.contract', $seminar), ['signed_on' => '2027-02-01'])->assertSessionHasErrors('file');
    $this->post(route('seminars.office.contract', $seminar), ['file' => rlPdf(), 'signed_on' => '2099-01-01'])->assertSessionHasErrors('signed_on');
    $this->post(route('seminars.office.contract', $seminar), ['file' => rlPdf(), 'signed_on' => now()->subDay()->toDateString()])->assertSessionHas('success');

    $seminar->refresh();

    expect($seminar->documents)->toHaveCount(1)->and($seminar->documents[0]->kind)->toBe('school_contract')->and($seminar->school_contract_signed_on)->not->toBeNull();
    Storage::disk('local')->assertExists($seminar->documents[0]->path);
});

it('refuses pictures and other file types as contract', function () {
    [$seminar] = rlSeminar();
    $this->actingAs(rlUser(RoleKey::AdministrationStaff));

    $this->post(route('seminars.office.contract', $seminar), ['file' => UploadedFile::fake()->createWithContent('scan.png', "\x89PNG\r\n"), 'signed_on' => now()->toDateString()])->assertSessionHasErrors('contract');
    $this->post(route('seminars.office.contract', $seminar), ['file' => UploadedFile::fake()->createWithContent('vertrag.exe', 'MZ'), 'signed_on' => now()->toDateString()])->assertSessionHasErrors('contract');

    expect($seminar->documents()->count())->toBe(0);
});

it('releases with the contract only when the signed contract is there', function () {
    [$seminar] = rlSeminar();
    $staff = rlUser(RoleKey::AdministrationStaff);
    $this->actingAs($staff);

    $this->post(route('seminars.office.release', $seminar), ['basis' => 'contract'])->assertSessionHasErrors('release');
    expect($seminar->fresh()->status)->toBe(SeminarStatus::QuoteSent);

    $this->post(route('seminars.office.contract', $seminar), ['file' => rlPdf(), 'signed_on' => now()->toDateString()]);
    $this->post(route('seminars.office.release', $seminar), ['basis' => 'contract'])->assertRedirect(route('seminars.office.show', $seminar))->assertSessionHas('success');

    $seminar->refresh();

    expect($seminar->status)->toBe(SeminarStatus::Released)->and($seminar->release_basis)->toBe('contract')->and($seminar->released_by_user_id)->toBe($staff->id)->and($seminar->released_at)->not->toBeNull()
        ->and(AuditEntry::query()->where('event', 'seminars.released')->count())->toBe(1);
});

it('releases on a binding commitment only with a reason', function () {
    [$seminar] = rlSeminar();
    $this->actingAs(rlUser(RoleKey::AdministrationStaff));

    $this->post(route('seminars.office.release', $seminar), ['basis' => 'commitment', 'reason' => ' '])->assertSessionHasErrors('release');
    $this->post(route('seminars.office.release', $seminar), ['basis' => 'unbekannt'])->assertSessionHasErrors('basis');
    $this->post(route('seminars.office.release', $seminar), ['basis' => 'commitment', 'reason' => 'Zusage per E-Mail vom 12.03., Vertrag folgt'])->assertSessionHas('success');

    expect($seminar->fresh()->status)->toBe(SeminarStatus::Released)->and($seminar->fresh()->release_basis)->toBe('commitment')->and($seminar->events()->first()->comment)->toContain('Zusage per E-Mail');

    // der Vertrag darf danach noch folgen
    $this->post(route('seminars.office.contract', $seminar), ['file' => rlPdf(), 'signed_on' => now()->toDateString()])->assertSessionHas('success');
});

it('does not release a seminar without a sent quote or in the wrong state', function () {
    [$seminar] = rlSeminar();
    $seminar->quotes()->delete();
    $this->actingAs(rlUser(RoleKey::AdministrationStaff));

    $this->post(route('seminars.office.release', $seminar), ['basis' => 'commitment', 'reason' => 'Zusage'])->assertSessionHasErrors('release');

    $other = Seminar::factory()->status(SeminarStatus::InReview)->create();
    $this->post(route('seminars.office.release', $other), ['basis' => 'commitment', 'reason' => 'Zusage'])->assertSessionHasErrors('release');

    $this->actingAs(rlUser(RoleKey::Coordination))->post(route('seminars.office.release', $seminar), ['basis' => 'commitment', 'reason' => 'Zusage'])->assertForbidden();
});

it('lets the school upload its own contract and download it, but nobody else’s', function () {
    [$seminar, $school] = rlSeminar();
    [$foreignSeminar, $foreign] = rlSeminar('Schule Zwei', 'zwei@schule.example');

    $this->actingAs($school)->get(route('school.seminars.show', $seminar))->assertOk()->assertSee('Ihr unterschriebener Vertrag');
    $this->post(route('school.seminars.contract', $seminar), ['file' => rlPdf(), 'signed_on' => now()->toDateString()])->assertSessionHas('success');
    $this->post(route('school.seminars.contract', $foreignSeminar), ['file' => rlPdf(), 'signed_on' => now()->toDateString()])->assertNotFound();

    $document = $seminar->documents()->firstOrFail();

    $this->get(route('seminars.documents.download', $document))->assertOk();
    $this->actingAs($foreign)->get(route('seminars.documents.download', $document))->assertNotFound();
    $this->actingAs(rlUser(RoleKey::Member))->get(route('seminars.documents.download', $document))->assertNotFound();
    $this->actingAs(rlUser(RoleKey::AdministrationStaff))->get(route('seminars.documents.download', $document))->assertOk();
    $this->actingAs(rlUser(RoleKey::Coordination))->get(route('seminars.documents.download', $document))->assertOk();
});
