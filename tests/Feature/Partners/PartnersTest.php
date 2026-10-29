<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\Partners\Enums\PartnerStatus;
use App\Modules\Partners\Models\Partner;
use App\Modules\Partners\Services\PartnerImporter;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

function ptUser(?RoleKey $role = null): User
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

function ptForm(array $overrides = []): array
{
    return $overrides + [
        'name' => 'Gesamtschule Musterstadt',
        'legal_name' => 'Stadt Musterstadt',
        'school_type' => 'gesamtschule',
        'federal_state' => 'SN',
        'school_number' => '12345',
        'student_count' => '650',
        'status' => 'active',
        'street' => 'Schulstraße',
        'house_number' => '1',
        'postal_code' => '04109',
        'city' => 'Leipzig',
        'country_code' => 'de',
        'contact_name' => 'Frau Beispiel',
        'contact_email' => 'Sekretariat@Beispiel.example',
        'contact_phone' => '0341 123456',
        'notes' => 'Erstkontakt im Mai',
    ];
}

function ptCsv(string $content, string $name = 'schulen.csv'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, $content);
}

const PT_HEADER = "Name;Träger;Schulart;Bundesland;Schulnummer;Straße;Hausnummer;PLZ;Ort;Ansprechperson;E-Mail;Telefon;Schülerzahl;Status;Notiz\n";

// ---------- Zugriff ----------

it('sends guests to the login and forbids administration and members', function () {
    $partner = Partner::factory()->create();

    $this->get(route('partners.index'))->assertRedirect(route('login'));

    foreach ([RoleKey::Member, RoleKey::Administration, RoleKey::Team] as $role) {
        $this->actingAs(ptUser($role))->get(route('partners.index'))->assertForbidden();
        $this->get(route('partners.show', $partner))->assertForbidden();
        $this->get(route('partners.create'))->assertForbidden();
        $this->get(route('partners.import'))->assertForbidden();
        $this->get(route('partners.export'))->assertForbidden();
    }
});

it('lets coordination, staff and board work with schools', function () {
    foreach ([RoleKey::Coordination, RoleKey::AdministrationStaff, RoleKey::BoardMember] as $role) {
        $this->actingAs(ptUser($role))->get(route('partners.index'))->assertOk();
        $this->get(route('partners.create'))->assertOk();
    }
});

it('shows the schools page in the area Koordination', function () {
    preg_match('/<header class="vdbs-header".*?<\/header>/s', $this->actingAs(ptUser(RoleKey::Coordination))->get(route('partners.index'))->getContent(), $header);

    expect($header[0])->toContain('aria-label="Seiten im Bereich Koordination"')->toMatch('/aria-current="page"\s*>Schulen</');
});

// ---------- Liste ----------

it('lists schools with stats, search, filters, sorting and a row link', function () {
    $a = Partner::factory()->create(['name' => 'Anne-Frank-Schule', 'city' => 'Leipzig', 'attributes' => ['school_type' => 'gymnasium', 'federal_state' => 'SN']]);
    Partner::factory()->create(['name' => 'Berufsschule Dresden', 'city' => 'Dresden', 'attributes' => ['school_type' => 'berufsschule', 'federal_state' => 'SN']]);
    Partner::factory()->inactive()->create(['name' => 'Alte Grundschule', 'city' => 'Hamburg', 'attributes' => ['school_type' => 'grundschule', 'federal_state' => 'HH']]);
    $this->actingAs(ptUser(RoleKey::Coordination));

    $this->get(route('partners.index'))->assertOk()->assertSee('Anne-Frank-Schule')->assertSee('Berufsschule Dresden')->assertSee('Gymnasium')->assertSee('Sachsen')
        ->assertSee('class="vdbs-table__row-link" href="'.route('partners.show', $a).'"', false);

    $this->get(route('partners.index', ['q' => 'Dresden']))->assertSee('Berufsschule Dresden')->assertDontSee('Anne-Frank-Schule');
    $this->get(route('partners.index', ['status' => 'inactive']))->assertSee('Alte Grundschule')->assertDontSee('Anne-Frank-Schule');
    $this->get(route('partners.index', ['school_type' => 'gymnasium']))->assertSee('Anne-Frank-Schule')->assertDontSee('Berufsschule Dresden');
    $this->get(route('partners.index', ['federal_state' => 'HH']))->assertSee('Alte Grundschule')->assertDontSee('Anne-Frank-Schule');
    $this->get(route('partners.index', ['sort' => 'city', 'direction' => 'desc']))->assertSeeInOrder(['Anne-Frank-Schule', 'Alte Grundschule', 'Berufsschule Dresden']);
    $this->get(route('partners.index', ['sort' => 'school_type']))->assertOk();
    $this->get(route('partners.index', ['sort' => 'federal_state', 'status' => 'unsinn', 'school_type' => 'x']))->assertOk();
});

it('escapes wildcards in the search and shows an empty state', function () {
    $this->actingAs(ptUser(RoleKey::Coordination));

    $this->get(route('partners.index'))->assertSee('Es sind noch keine Schulen erfasst');

    Partner::factory()->create(['name' => 'Schule A']);
    $this->get(route('partners.index', ['q' => '%']))->assertSee('Keine Schulen gefunden')->assertDontSee('Schule A');
});

it('paginates with twenty schools per page', function () {
    Partner::factory()->count(21)->create();

    $this->actingAs(ptUser(RoleKey::Coordination))->get(route('partners.index'))->assertOk()->assertSee('21 Schulen')->assertSee('page=2', false);
});

// ---------- Anlegen und ändern ----------

it('creates a school with normalized values and the creator', function () {
    $coordinator = ptUser(RoleKey::Coordination);

    $this->actingAs($coordinator)->post(route('partners.store'), ptForm())->assertRedirect()->assertSessionHas('success');

    $partner = Partner::query()->firstOrFail();

    expect($partner->name)->toBe('Gesamtschule Musterstadt')
        ->and($partner->type->value)->toBe('school')
        ->and($partner->status)->toBe(PartnerStatus::Active)
        ->and($partner->country_code)->toBe('DE')
        ->and($partner->contact_email)->toBe('sekretariat@beispiel.example')
        ->and($partner->public_id)->toHaveLength(26)
        ->and($partner->created_by_user_id)->toBe($coordinator->id)
        ->and($partner->getAttribute('attributes'))->toEqual(['school_type' => 'gesamtschule', 'federal_state' => 'SN', 'school_number' => '12345', 'student_count' => 650]);
});

it('stores empty optional values as null', function () {
    $this->actingAs(ptUser(RoleKey::Coordination))->post(route('partners.store'), ['name' => 'Minimalschule', 'status' => 'active', 'country_code' => 'DE'])->assertSessionHasNoErrors();

    $partner = Partner::query()->firstOrFail();

    expect($partner->city)->toBeNull()->and($partner->contact_email)->toBeNull()->and($partner->getAttribute('attributes'))->toBeNull();
});

it('validates the school form', function () {
    $this->actingAs(ptUser(RoleKey::Coordination));

    $this->post(route('partners.store'), [])->assertSessionHasErrors(['name', 'status', 'country_code']);
    $this->post(route('partners.store'), ptForm(['school_type' => 'unsinn']))->assertSessionHasErrors('school_type');
    $this->post(route('partners.store'), ptForm(['federal_state' => 'XX']))->assertSessionHasErrors('federal_state');
    $this->post(route('partners.store'), ptForm(['student_count' => '-3']))->assertSessionHasErrors('student_count');
    $this->post(route('partners.store'), ptForm(['student_count' => 'viele']))->assertSessionHasErrors('student_count');
    $this->post(route('partners.store'), ptForm(['contact_email' => 'kaputt']))->assertSessionHasErrors('contact_email');
    $this->post(route('partners.store'), ptForm(['contact_phone' => 'abc']))->assertSessionHasErrors('contact_phone');
    $this->post(route('partners.store'), ptForm(['country_code' => 'Deutschland']))->assertSessionHasErrors('country_code');
    $this->post(route('partners.store'), ptForm(['status' => 'weg']))->assertSessionHasErrors('status');

    expect(Partner::query()->count())->toBe(0);
});

it('refuses the same school name in the same city but allows it elsewhere', function () {
    $this->actingAs(ptUser(RoleKey::Coordination));
    $this->post(route('partners.store'), ptForm())->assertSessionHasNoErrors();

    $this->post(route('partners.store'), ptForm(['name' => 'GESAMTSCHULE musterstadt', 'city' => 'leipzig']))->assertSessionHasErrors('name');
    $this->post(route('partners.store'), ptForm(['city' => 'Dresden']))->assertSessionHasNoErrors();

    expect(Partner::query()->count())->toBe(2);
});

it('edits a school and records only the changed field names in the log', function () {
    $coordinator = ptUser(RoleKey::Coordination);
    $partner = Partner::factory()->create(['name' => 'Schule', 'city' => 'Leipzig']);
    $this->actingAs($coordinator);

    $this->get(route('partners.edit', $partner))->assertOk()->assertSee('Schule bearbeiten');

    $this->put(route('partners.update', $partner), ptForm(['name' => 'Schule', 'city' => 'Leipzig', 'notes' => 'Geheime Notiz']))->assertRedirect(route('partners.show', $partner));

    $entry = AuditEntry::query()->where('event', 'partner.updated')->firstOrFail();

    expect($entry->subject_type)->toBe('partner')->and($entry->subject_id)->toBe($partner->id)->and($entry->subject_label)->toBe('Schule')
        ->and($entry->context['felder'])->toContain('notes', 'legal_name')
        ->and(json_encode($entry->context))->not->toContain('Geheime Notiz')
        ->and($partner->fresh()->updated_by_user_id)->toBe($coordinator->id);
});

it('does not log an update without changes and lets a school keep its own name', function () {
    $partner = Partner::factory()->create(['name' => 'Schule', 'city' => 'Leipzig', 'country_code' => 'DE']);
    $this->actingAs(ptUser(RoleKey::Coordination));

    $this->put(route('partners.update', $partner), ['name' => 'Schule', 'city' => 'Leipzig', 'status' => 'active', 'country_code' => 'DE', 'school_type' => 'gymnasium', 'federal_state' => 'SN'])->assertSessionHasNoErrors();

    expect(AuditEntry::query()->where('event', 'partner.updated')->count())->toBe(0);

    $other = Partner::factory()->create(['name' => 'Andere', 'city' => 'Leipzig']);
    $this->put(route('partners.update', $other), ['name' => 'Schule', 'city' => 'Leipzig', 'status' => 'active', 'country_code' => 'DE'])->assertSessionHasErrors('name');
});

it('deactivates a school through the status', function () {
    $partner = Partner::factory()->create();

    $this->actingAs(ptUser(RoleKey::Coordination))->put(route('partners.update', $partner), ptForm(['name' => $partner->name, 'city' => $partner->city, 'status' => 'inactive']));

    expect($partner->fresh()->status)->toBe(PartnerStatus::Inactive);
});

it('deletes a school and logs it', function () {
    $partner = Partner::factory()->create(['name' => 'Weg damit']);

    $this->actingAs(ptUser(RoleKey::AdministrationStaff))->delete(route('partners.destroy', $partner))->assertRedirect(route('partners.index'));

    expect(Partner::query()->count())->toBe(0)->and(AuditEntry::query()->where('event', 'partner.deleted')->value('subject_label'))->toBe('Weg damit');
});

// ---------- Details und Verlauf ----------

it('shows the details of a school', function () {
    $partner = Partner::factory()->create([
        'name' => 'Gesamtschule Musterstadt', 'legal_name' => 'Stadt Musterstadt', 'street' => 'Schulstraße', 'house_number' => '1', 'postal_code' => '04109', 'city' => 'Leipzig',
        'contact_name' => 'Frau Beispiel', 'contact_email' => 'sekretariat@beispiel.example', 'contact_phone' => '0341 123456', 'notes' => 'Erstkontakt im Mai',
        'attributes' => ['school_type' => 'gesamtschule', 'federal_state' => 'SN', 'school_number' => '12345', 'student_count' => 650],
    ]);

    $this->actingAs(ptUser(RoleKey::Coordination))->get(route('partners.show', $partner))
        ->assertOk()
        ->assertSee('Gesamtschule Musterstadt')
        ->assertSee('Gesamtschule / Gemeinschaftsschule')
        ->assertSee('Sachsen')
        ->assertSee('12345')
        ->assertSee('650')
        ->assertSee('Schulstraße 1, 04109 Leipzig')
        ->assertSee('mailto:sekretariat@beispiel.example', false)
        ->assertSee('Erstkontakt im Mai');
});

it('shows the history only to those who may read the log and links it from the log', function () {
    $this->actingAs(ptUser(RoleKey::BoardMember))->post(route('partners.store'), ptForm());
    $partner = Partner::query()->firstOrFail();

    $this->get(route('partners.show', $partner))->assertOk()->assertSee('Verlauf')->assertSee('Schule angelegt');
    $this->get(route('admin.audit.index', ['subject' => 'partner:'.$partner->id]))->assertOk()->assertSee('Gesamtschule Musterstadt');
    $this->get(route('admin.audit.show', AuditEntry::query()->firstOrFail()))->assertOk()->assertSee(route('partners.show', $partner), false);

    $this->actingAs(ptUser(RoleKey::Coordination))->get(route('partners.show', $partner))->assertOk()->assertDontSee('Schule angelegt');
});

// ---------- Export ----------

it('exports the filtered schools as csv with german labels and formula protection', function () {
    Partner::factory()->create(['name' => '=HYPERLINK("x")', 'city' => 'Leipzig', 'attributes' => ['school_type' => 'gymnasium', 'federal_state' => 'SN', 'student_count' => 400]]);
    Partner::factory()->inactive()->create(['name' => 'Andere Schule', 'city' => 'Hamburg']);

    $response = $this->actingAs(ptUser(RoleKey::Coordination))->get(route('partners.export', ['status' => 'active']));
    $csv = $response->streamedContent();

    $response->assertOk()->assertDownload();
    expect(str_starts_with($csv, "\xEF\xBB\xBF"))->toBeTrue()
        ->and($csv)->toContain('Name;Träger;Schulart;Bundesland')
        ->toContain("'=HYPERLINK")
        ->toContain('Gymnasium;Sachsen')
        ->toContain(';400;aktiv')
        ->not->toContain('Andere Schule');
});

it('offers an import template', function () {
    $response = $this->actingAs(ptUser(RoleKey::Coordination))->get(route('partners.import.template'));

    expect($response->streamedContent())->toContain('Name;Träger;Schulart')->toContain('Beispielschule');
});

// ---------- Import ----------

it('imports schools from a semicolon csv with a byte order mark', function () {
    $coordinator = ptUser(RoleKey::Coordination);
    $csv = "\xEF\xBB\xBF".PT_HEADER
        ."Anne-Frank-Schule;Stadt Leipzig;Gymnasium;Sachsen;S-1;Schulstraße;1;04109;Leipzig;Frau Beispiel;Sek@Beispiel.example;0341 1;650;aktiv;Notiz eins\n"
        ."Grundschule Süd;;grundschule;SN;;;;;Dresden;;;;120;inaktiv;\n"
        ."\n";

    $this->actingAs($coordinator)->post(route('partners.import.store'), ['file' => ptCsv($csv)])->assertRedirect(route('partners.index'))->assertSessionHas('success', '2 Schulen importiert.');

    $anne = Partner::query()->where('name', 'Anne-Frank-Schule')->firstOrFail();
    $south = Partner::query()->where('name', 'Grundschule Süd')->firstOrFail();

    expect($anne->getAttribute('attributes'))->toEqual(['school_type' => 'gymnasium', 'federal_state' => 'SN', 'school_number' => 'S-1', 'student_count' => 650])
        ->and($anne->contact_email)->toBe('sek@beispiel.example')
        ->and($anne->created_by_user_id)->toBe($coordinator->id)
        ->and($south->status)->toBe(PartnerStatus::Inactive)
        ->and($south->getAttribute('attributes'))->toEqual(['school_type' => 'grundschule', 'federal_state' => 'SN', 'student_count' => 120]);

    $entry = AuditEntry::query()->where('event', 'partner.imported')->firstOrFail();
    expect($entry->context)->toEqual(['angelegt' => 2, 'uebersprungen' => 0]);
});

it('reads comma separated and windows-1252 files', function () {
    $comma = "Name,Ort,Schulart\nKomma-Schule,Bonn,Gymnasium\n";
    $this->actingAs(ptUser(RoleKey::Coordination))->post(route('partners.import.store'), ['file' => ptCsv($comma)])->assertSessionHas('success');

    $ansi = mb_convert_encoding("Name;Straße;Ort\nGrüne Schule;Müllerstraße;Köln\n", 'Windows-1252', 'UTF-8');
    $this->post(route('partners.import.store'), ['file' => ptCsv($ansi)])->assertSessionHas('success');

    expect(Partner::query()->where('name', 'Komma-Schule')->exists())->toBeTrue()
        ->and(Partner::query()->where('name', 'Grüne Schule')->first()?->street)->toBe('Müllerstraße');
});

it('skips schools that already exist and reports it', function () {
    Partner::factory()->create(['name' => 'Anne-Frank-Schule', 'city' => 'Leipzig', 'notes' => 'Bestand']);

    $this->actingAs(ptUser(RoleKey::Coordination))->post(route('partners.import.store'), ['file' => ptCsv("Name;Ort\nanne-frank-schule;LEIPZIG\nNeue Schule;Leipzig\n")])
        ->assertSessionHas('success', '1 Schulen importiert, 1 übersprungen (gibt es schon).');

    expect(Partner::query()->count())->toBe(2)->and(Partner::query()->where('name', 'Anne-Frank-Schule')->value('notes'))->toBe('Bestand');
});

it('imports nothing and names every faulty line when the file has errors', function () {
    $csv = PT_HEADER
        ."Gute Schule;;Gymnasium;Sachsen;;;;;Leipzig;;;;100;aktiv;\n"
        .";;Gymnasium;;;;;;Leipzig;;;;;;\n"
        ."Schlechte Schule;;Zauberschule;Atlantis;;;;;Berlin;;kaputt;;viele;;\n"
        ."Gute Schule;;;;;;;;Leipzig;;;;;;\n";

    $this->actingAs(ptUser(RoleKey::Coordination))->post(route('partners.import.store'), ['file' => ptCsv($csv)])
        ->assertRedirect(route('partners.import'))
        ->assertSessionHas('import_error_count', 3);

    $errors = session('import_errors');

    expect(Partner::query()->count())->toBe(0)
        ->and($errors[0])->toStartWith('Zeile 3:')->and($errors[0])->toContain('Namen der Schule')
        ->and($errors[1])->toStartWith('Zeile 4:')
        ->and($errors[2])->toStartWith('Zeile 5:')->and($errors[2])->toContain('Zeile 2');

    $this->get(route('partners.import'))->assertOk()->assertSee('wurde nicht importiert')->assertSee('Zeile 3');
});

it('rejects unknown columns, a missing name column and empty files', function () {
    $this->actingAs(ptUser(RoleKey::Coordination));

    $this->post(route('partners.import.store'), ['file' => ptCsv("Name;Lieblingsfarbe\nSchule;Blau\n")]);
    expect(session('import_errors')[0])->toContain('Unbekannte Spalten: Lieblingsfarbe');

    $this->post(route('partners.import.store'), ['file' => ptCsv("Ort;Schulart\nLeipzig;Gymnasium\n")]);
    expect(session('import_errors')[0])->toContain('Die Spalte „Name“ fehlt');

    $this->post(route('partners.import.store'), ['file' => ptCsv("Name;Ort\n")]);
    expect(session('import_errors')[0])->toContain('keine Schulen');

    $this->post(route('partners.import.store'), ['file' => ptCsv("\n")]);
    expect(session('import_errors')[0])->toContain('leer');

    expect(Partner::query()->count())->toBe(0);
});

it('limits the import to a thousand rows', function () {
    $rows = 'Name;Ort'."\n";

    foreach (range(1, PartnerImporter::MAX_ROWS + 1) as $i) {
        $rows .= "Schule {$i};Stadt\n";
    }

    $this->actingAs(ptUser(RoleKey::Coordination))->post(route('partners.import.store'), ['file' => ptCsv($rows)]);

    expect(session('import_errors')[0])->toContain('mehr als 1000 Zeilen')->and(Partner::query()->count())->toBe(0);
});

it('validates the uploaded file and forbids the import without permission', function () {
    $this->actingAs(ptUser(RoleKey::Coordination));

    $this->post(route('partners.import.store'), [])->assertSessionHasErrors('file');
    $this->post(route('partners.import.store'), ['file' => UploadedFile::fake()->create('schulen.pdf', 10, 'application/pdf')])->assertSessionHasErrors('file');
    $this->post(route('partners.import.store'), ['file' => UploadedFile::fake()->create('gross.csv', 3000, 'text/csv')])->assertSessionHasErrors('file');

    $this->actingAs(ptUser(RoleKey::Administration))->post(route('partners.import.store'), ['file' => ptCsv("Name\nX\n")])->assertForbidden();
});

it('maps school type, federal state and status flexibly', function () {
    $csv = "Name;Schulart;Bundesland;Status;Ort\nA;Berufsschule / Berufskolleg;nordrhein-westfalen;;X\nB;FOERDERSCHULE;by;active;Y\nC;sonstige;Thüringen;inactive;Z\n";

    $this->actingAs(ptUser(RoleKey::Coordination))->post(route('partners.import.store'), ['file' => ptCsv($csv)])->assertSessionHas('success');

    expect(Partner::query()->where('name', 'A')->first()->getAttribute('attributes'))->toEqual(['school_type' => 'berufsschule', 'federal_state' => 'NW'])
        ->and(Partner::query()->where('name', 'B')->first()->getAttribute('attributes'))->toEqual(['school_type' => 'foerderschule', 'federal_state' => 'BY'])
        ->and(Partner::query()->where('name', 'C')->first()->status)->toBe(PartnerStatus::Inactive);
});
