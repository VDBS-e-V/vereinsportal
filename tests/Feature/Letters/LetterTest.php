<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\Letters\Models\LetterTemplate;
use App\Modules\Letters\Support\SampleLetter;
use App\Modules\Partners\Models\Partner;
use App\Modules\People\Models\Person;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use App\Modules\Seminars\Services\SeminarAdministration;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Storage::fake('local');
});

function ltUser(?RoleKey $role, array $person = []): User
{
    $p = Person::factory()->create($person);
    $user = User::factory()->create(['person_id' => $p->id, 'email' => $p->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

/** @param  list<string>  $paragraphs */
function ltDocx(array $paragraphs, string $name = 'vorlage.docx'): UploadedFile
{
    return new UploadedFile(SampleLetter::build($paragraphs), $name, null, null, true);
}

function ltTemplate(array $paragraphs, string $name = 'Vorlage', string $audience = 'general'): LetterTemplate
{
    test()->actingAs(ltUser(RoleKey::AdministrationStaff))->post(route('letters.store'), ['name' => $name, 'audience' => $audience, 'file' => ltDocx($paragraphs)])->assertRedirect();

    return LetterTemplate::query()->where('name', $name)->firstOrFail();
}

function ltXml(string $path): string
{
    $zip = new ZipArchive;
    $zip->open($path);
    $xml = (string) $zip->getFromName('word/document.xml');
    $zip->close();

    return $xml;
}

/** @return array{0: Seminar, 1: list<SeminarApplication>} */
function ltSeminar(): array
{
    $partner = Partner::factory()->create(['name' => 'Müller & Söhne Schule', 'street' => 'Schulweg', 'house_number' => '3', 'postal_code' => '12345', 'city' => 'Berlin']);
    $seminar = Seminar::factory()->create(['status' => SeminarStatus::Scheduled, 'title' => 'SV-Tage <Nord>', 'partner_id' => $partner->id]);
    app(SeminarAdministration::class)->addSession($seminar, ['date' => now()->addMonth()->toDateString(), 'start' => '09:00', 'end' => '15:00'], ltUser(RoleKey::Coordination));

    $applications = [];

    foreach ([['Anna', 'Alt'], ['Ben', 'Bunt']] as [$first, $last]) {
        $user = ltUser(RoleKey::Team, ['first_name' => $first, 'last_name' => $last, 'street' => 'Musterweg', 'house_number' => '5', 'postal_code' => '10115', 'city' => 'Berlin']);
        $applications[] = SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'user_id' => $user->id, 'status' => 'confirmed']);
    }

    return [$seminar->fresh(), $applications];
}

it('hands out a valid sample template with placeholders', function () {
    $this->actingAs(ltUser(RoleKey::Coordination));
    $response = $this->get(route('letters.sample'))->assertOk();
    $xml = ltXml($response->baseResponse->getFile()->getPathname());

    expect($xml)->toContain('${datum}')->toContain('${empfaenger_name}');
});

it('stores a word template, reads its placeholders and warns about unknown ones', function () {
    $template = ltTemplate(['Guten Tag ${empfaenger_name}', '${datum}', '${tippfehler}'], 'Bestätigung');

    expect($template->variableNames())->toEqualCanonicalizing(['empfaenger_name', 'datum', 'tippfehler']);
    expect(Storage::disk('local')->exists($template->path))->toBeTrue();

    $this->actingAs(ltUser(RoleKey::AdministrationStaff));
    $this->get(route('letters.index'))->assertOk()->assertSee('Bestätigung')->assertSee('1 unbekannt');
    $this->get(route('letters.edit', $template))->assertOk()->assertSee('Unbekannte Platzhalter')->assertSee('tippfehler');
    expect(AuditEntry::query()->where('event', 'letter.template_saved')->exists())->toBeTrue();
});

it('refuses files that are not clean word documents and duplicate names', function () {
    $staff = ltUser(RoleKey::AdministrationStaff);
    $this->actingAs($staff);

    $text = UploadedFile::fake()->createWithContent('brief.docx', 'kein zip');
    $this->post(route('letters.store'), ['name' => 'A', 'audience' => 'general', 'file' => $text])->assertSessionHas('error');

    $pdf = UploadedFile::fake()->createWithContent('brief.pdf', '%PDF');
    $this->post(route('letters.store'), ['name' => 'B', 'audience' => 'general', 'file' => $pdf])->assertSessionHas('error');

    $path = SampleLetter::build(['x']);
    $zip = new ZipArchive;
    $zip->open($path);
    $zip->addFromString('word/vbaProject.bin', 'makro');
    $zip->close();
    $this->post(route('letters.store'), ['name' => 'C', 'audience' => 'general', 'file' => new UploadedFile($path, 'makro.docx', null, null, true)])->assertSessionHas('error');

    expect(LetterTemplate::query()->count())->toBe(0);

    ltTemplate(['${datum}'], 'Doppelt');
    $this->post(route('letters.store'), ['name' => 'Doppelt', 'audience' => 'general', 'file' => ltDocx(['${datum}'])])->assertSessionHas('error');
    expect(LetterTemplate::query()->count())->toBe(1);
});

it('lets the coordination use templates but only staff and board change them', function () {
    $template = ltTemplate(['${datum}'], 'Vorlage');

    $this->actingAs(ltUser(RoleKey::Coordination));
    $this->get(route('letters.index'))->assertOk();
    $this->get(route('letters.variables'))->assertOk();
    $this->get(route('letters.download', $template))->assertOk();
    $this->get(route('letters.create'))->assertForbidden();
    $this->post(route('letters.store'), ['name' => 'X', 'audience' => 'general', 'file' => ltDocx(['x'])])->assertForbidden();
    $this->delete(route('letters.destroy', $template))->assertForbidden();

    foreach ([RoleKey::Team, RoleKey::Member, RoleKey::School] as $role) {
        $this->actingAs(ltUser($role))->get(route('letters.index'))->assertForbidden();
    }

    $this->actingAs(ltUser(RoleKey::BoardMember))->put(route('letters.update', $template), ['name' => 'Neu', 'audience' => 'school'])->assertRedirect();
    expect($template->fresh()->name)->toBe('Neu')->and($template->fresh()->audience)->toBe('school');

    $this->delete(route('letters.destroy', $template))->assertRedirect();
    expect(LetterTemplate::query()->count())->toBe(0);
});

it('fills a letter for the school with escaped values, line breaks and the data of the seminar', function () {
    [$seminar] = ltSeminar();
    $template = ltTemplate(['${empfaenger_name}', '${empfaenger_anschrift}', '${seminar_titel} (${seminar_nr})', '${seminar_termine}', '${betreff}: ${freitext}', '${absender_name}', '${tippfehler}'], 'Schulbrief', 'school');
    $coordination = ltUser(RoleKey::Coordination, ['first_name' => 'Karla', 'last_name' => 'Koordina']);

    $response = $this->actingAs($coordination)->post(route('letters.generate', $seminar), ['template_id' => $template->id, 'to' => 'school', 'subject' => 'Bestätigung', 'freitext' => "Zeile eins\nZeile <zwei> & drei"]);
    $response->assertOk();
    $xml = ltXml($response->baseResponse->getFile()->getPathname());

    expect($xml)->toContain('Müller &amp; Söhne Schule')->toContain('Schulweg 3')->toContain('12345 Berlin')->toContain('SV-Tage &lt;Nord&gt;')->toContain((string) $seminar->number)
        ->toContain('Zeile eins')->toContain('Zeile &lt;zwei&gt; &amp; drei')->toContain('<w:br/>')->toContain('Karla Koordina')->toContain('${tippfehler}')
        ->not->toContain('${empfaenger_name}')->not->toContain('${datum}');
    expect(AuditEntry::query()->where('event', 'letter.generated')->count())->toBe(1);
});

it('creates one file per confirmed person, as a zip for several and as a single file for one', function () {
    [$seminar, $applications] = ltSeminar();
    $template = ltTemplate(['Liebe/r ${empfaenger_vorname} ${empfaenger_nachname}', '${empfaenger_anschrift}', '${schule_name}'], 'Teambrief', 'team');
    $this->actingAs(ltUser(RoleKey::Coordination));

    $response = $this->post(route('letters.generate', $seminar), ['template_id' => $template->id, 'to' => 'team'])->assertOk();
    $zip = new ZipArchive;
    $zip->open($response->baseResponse->getFile()->getPathname());

    expect($zip->numFiles)->toBe(2)->and(strtolower($response->baseResponse->getFile()->getExtension()))->toBe('zip');

    $names = [];

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $names[] = $zip->getNameIndex($i);
    }

    expect(implode(' ', $names))->toContain('Anna-Alt')->toContain('Ben-Bunt');
    $zip->close();

    $single = $this->post(route('letters.generate', $seminar), ['template_id' => $template->id, 'to' => 'team', 'applications' => [$applications[1]->id]])->assertOk();
    $xml = ltXml($single->baseResponse->getFile()->getPathname());

    expect($xml)->toContain('Ben')->toContain('Bunt')->toContain('Musterweg 5')->toContain('10115 Berlin')->toContain('Müller &amp; Söhne Schule')->not->toContain('Anna');
});

it('shows the letters section on the seminar page and in the navigation to those who may use it', function () {
    [$seminar] = ltSeminar();
    ltTemplate(['${datum}'], 'Vorlage');

    $this->actingAs(ltUser(RoleKey::Coordination))->get(route('seminars.show', $seminar))->assertOk()->assertSee('Schreiben erzeugen');
    $this->get(route('coordination.home'))->assertOk()->assertSee('Briefvorlagen');
    $this->post(route('letters.generate', $seminar), ['template_id' => 999, 'to' => 'school'])->assertSessionHasErrors('template_id');
});

it('shows or hides blocks with if-queries by value and by recipient kind, also when a block appears twice', function () {
    [$seminar, $applications] = ltSeminar();
    $template = ltTemplate([
        'Anfang',
        '${wenn_ansprechperson}', 'Ihre Ansprechperson: ${ansprechperson}', '${/wenn_ansprechperson}',
        '${ohne_ansprechperson}', 'Keine Ansprechperson bekannt', '${/ohne_ansprechperson}',
        '${wenn_empfaenger_ist_schule}', 'Text nur für Schulen', '${/wenn_empfaenger_ist_schule}',
        '${wenn_empfaenger_ist_person}', 'Text nur für Personen', '${/wenn_empfaenger_ist_person}',
        '${wenn_freitext}', 'Zusatz eins', '${/wenn_freitext}',
        '${wenn_freitext}', 'Zusatz zwei', '${/wenn_freitext}',
        '${wenn_unbekannt_xyz}', 'bleibt', '${/wenn_unbekannt_xyz}',
        'Ende',
    ], 'Bedingungen');

    expect($template->variableNames())->toContain('wenn_ansprechperson', '/wenn_ansprechperson');

    $this->actingAs(ltUser(RoleKey::AdministrationStaff))->get(route('letters.edit', $template))->assertOk()->assertSee('Unbekannte Platzhalter')->assertSee('wenn_unbekannt_xyz');

    $this->actingAs(ltUser(RoleKey::Coordination));
    $school = $this->post(route('letters.generate', $seminar), ['template_id' => $template->id, 'to' => 'school'])->assertOk();
    $xml = ltXml($school->baseResponse->getFile()->getPathname());

    expect($xml)->toContain('Anfang')->toContain('Keine Ansprechperson bekannt')->toContain('Text nur für Schulen')->toContain('Ende')
        ->not->toContain('Ihre Ansprechperson')->not->toContain('Text nur für Personen')->not->toContain('Zusatz eins')
        ->toContain('${wenn_unbekannt_xyz}')->not->toContain('${wenn_ansprechperson}')->not->toContain('${/wenn_ansprechperson}');

    \App\Modules\Partners\Models\PartnerContact::query()->create(['partner_id' => $seminar->partner_id, 'name' => 'Frau Lehmann', 'email' => 'lehmann@schule.example', 'is_primary' => true]);

    $with = $this->post(route('letters.generate', $seminar), ['template_id' => $template->id, 'to' => 'school', 'freitext' => 'Etwas Text'])->assertOk();
    $xml = ltXml($with->baseResponse->getFile()->getPathname());

    expect($xml)->toContain('Ihre Ansprechperson: Frau Lehmann')->not->toContain('Keine Ansprechperson bekannt')->toContain('Zusatz eins')->toContain('Zusatz zwei');

    $person = $this->post(route('letters.generate', $seminar), ['template_id' => $template->id, 'to' => 'team', 'applications' => [$applications[0]->id]])->assertOk();
    $xml = ltXml($person->baseResponse->getFile()->getPathname());

    expect($xml)->toContain('Text nur für Personen')->not->toContain('Text nur für Schulen');
});

it('fills the extended placeholders for school, seminar and team and hands out the list as a word file', function () {
    [$seminar] = ltSeminar();
    $partner = $seminar->partner;
    $partner->forceFill(['phone' => '030 123456', 'email' => 'schule@beispiel.example', 'attributes' => ['federal_state' => 'BE', 'school_number' => '08K06', 'school_type' => 'gymnasium']])->save();
    $template = ltTemplate(['${schule_telefon}|${schule_email}|${schule_nummer}|${schule_bundesland}|${schule_schulart}|${seminar_beginn}|${seminar_tage}|${seminar_stunden}|${seminar_team}'], 'Erweitert');
    $this->actingAs(ltUser(RoleKey::Coordination));

    $xml = ltXml($this->post(route('letters.generate', $seminar), ['template_id' => $template->id, 'to' => 'team'])->assertOk()->baseResponse->getFile()->getPathname());

    expect($xml)->toContain('030 123456')->toContain('schule@beispiel.example')->toContain('BE/08K06')->toContain('Berlin')->toContain('Gymnasium')
        ->toContain($seminar->sessions()->first()->starts_at->format('d.m.Y'))->toContain('|1|')->toContain('6')->toContain('Anna Alt, Ben Bunt');

    $file = $this->get(route('letters.variables-file'))->assertOk();
    $list = ltXml($file->baseResponse->getFile()->getPathname());

    expect($list)->toContain('${datum}')->toContain('${seminar_team}')->toContain('${empfaenger_ist_schule}')
        ->toContain('Beispiel:')->toContain('Quelle:')->toContain('WENN-ABFRAGEN')->toContain('${wenn_ansprechperson}');
    $this->get(route('letters.variables'))->assertOk()->assertSee('Wenn-Abfragen')->assertSee('Liste als Word-Datei')
        ->assertSee('Fertige Beispiele zum Kopieren')->assertSee('Honorar nur nennen, wenn bekannt')->assertSee('Quelle und Hinweis');
});
