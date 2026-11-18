<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\People\Models\Person;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use App\Modules\Seminars\Services\PrintPlans;
use App\Modules\Seminars\Services\SeminarDocuments;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Storage::fake('local');
});

function sfUser(?RoleKey $role = null): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

/** Echte PDF mit der gewünschten Seitenzahl. */
function sfPdf(string $name, int $pages): UploadedFile
{
    $pdf = new FPDF;

    for ($i = 1; $i <= $pages; $i++) {
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Cell(0, 10, $name.' Seite '.$i);
    }

    return UploadedFile::fake()->createWithContent($name, (string) $pdf->Output('S'));
}

function sfPageCount(string $content): int
{
    return (new Fpdi)->setSourceFile(StreamReader::createByString($content));
}

/** Seminar mit Leitung Anna, Zusage Ben und einer Absage Carl. */
function sfSeminar(): array
{
    $anna = sfUser(RoleKey::Team);
    $ben = sfUser(RoleKey::Team);
    $carl = sfUser(RoleKey::Team);
    $seminar = Seminar::factory()->status(SeminarStatus::Prepared)->create(['lead_user_id' => $anna->id]);
    SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'user_id' => $anna->id, 'outcome' => 'confirmed']);
    SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'user_id' => $ben->id, 'outcome' => 'confirmed']);
    SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'user_id' => $carl->id, 'outcome' => 'declined']);

    return [$seminar, $anna, $ben, $carl];
}

it('reads the page range and refuses wrong ones', function () {
    expect(PrintPlans::parsePages('alle', 4))->toBe([1, 2, 3, 4])->and(PrintPlans::parsePages('', 2))->toBe([1, 2])->and(PrintPlans::parsePages('1-3, 5', 6))->toBe([1, 2, 3, 5])->and(PrintPlans::parsePages('2', 3))->toBe([2])
        ->and(PrintPlans::parsePages('0-2', 5))->toBeNull()->and(PrintPlans::parsePages('3-2', 5))->toBeNull()->and(PrintPlans::parsePages('1-9', 5))->toBeNull()->and(PrintPlans::parsePages('a-b', 5))->toBeNull();
});

it('counts sheets and paper with copies, pages per sheet and duplex', function () {
    $item = ['pages' => 'alle', 'copies' => 3, 'per_sheet' => 2, 'duplex' => true];

    expect(PrintPlans::counts($item, 7))->toBe(['pages' => 7, 'sheets' => 4, 'paper' => 2, 'total_paper' => 6, 'total_sheets' => 12])
        ->and(PrintPlans::counts(['pages' => '1-2', 'copies' => 10, 'per_sheet' => 1, 'duplex' => false], 9)['total_paper'])->toBe(20);
});

it('stores the page count of pdf uploads and marks other files as not printable', function () {
    [$seminar, $anna] = sfSeminar();
    $documents = app(SeminarDocuments::class);

    $pdf = $documents->add($seminar, sfPdf('material.pdf', 3), 'material', null, $anna);
    $doc = $documents->add($seminar, UploadedFile::fake()->createWithContent('anleitung.txt', 'Hinweise für das Seminar'), 'other', null, $anna);
    $contract = $documents->add($seminar, sfPdf('vertrag.pdf', 2), 'team_contract', null, $anna);

    expect($pdf->pages)->toBe(3)->and($doc->pages)->toBeNull()->and($contract->pages)->toBe(2);

    $plans = app(PrintPlans::class);

    expect($plans->printable($seminar)->pluck('id')->all())->toBe([$pdf->id])->and($plans->unusable($seminar)->pluck('id')->all())->toBe([$doc->id]);
});

it('validates the planner entries', function () {
    [$seminar, $anna] = sfSeminar();
    $doc = app(SeminarDocuments::class)->add($seminar, sfPdf('material.pdf', 3), 'material', null, $anna);
    $plans = app(PrintPlans::class);

    expect($plans->normalize($seminar, [$doc->id => ['use' => '1', 'pages' => '1-2', 'copies' => '4', 'color' => 'color', 'per_sheet' => '2', 'duplex' => '1']]))
        ->toBe([['document_id' => $doc->id, 'pages' => '1-2', 'copies' => 4, 'color' => true, 'per_sheet' => 2, 'duplex' => true]]);

    expect(fn () => $plans->normalize($seminar, []))->toThrow(DomainException::class, 'mindestens ein Dokument');
    expect(fn () => $plans->normalize($seminar, [$doc->id => ['use' => '1', 'pages' => '1-9', 'copies' => '1', 'per_sheet' => '1']]))->toThrow(DomainException::class, 'stimmen die Seiten nicht');
    expect(fn () => $plans->normalize($seminar, [$doc->id => ['use' => '1', 'pages' => 'alle', 'copies' => '0', 'per_sheet' => '1']]))->toThrow(DomainException::class, 'Exemplare');
    expect(fn () => $plans->normalize($seminar, [$doc->id => ['use' => '1', 'pages' => 'alle', 'copies' => '1', 'per_sheet' => '3']]))->toThrow(DomainException::class, 'Seiten pro Blatt');
    expect(fn () => $plans->normalize($seminar, [9999 => ['use' => '1']]))->toThrow(DomainException::class, 'gibt es nicht');
});

it('renders one pdf: cover, black and white first, copies and pages per sheet counted', function () {
    [$seminar, $anna] = sfSeminar();
    $documents = app(SeminarDocuments::class);
    $a = $documents->add($seminar, sfPdf('a.pdf', 3), 'material', null, $anna);
    $b = $documents->add($seminar, sfPdf('b.pdf', 4), 'material', null, $anna);
    $plans = app(PrintPlans::class);

    // b (Farbe) steht im Plan zuerst, kommt in der PDF aber nach a (Schwarzweiß)
    $items = $plans->normalize($seminar, [$b->id => ['use' => '1', 'pages' => 'alle', 'copies' => '1', 'color' => 'color', 'per_sheet' => '2'], $a->id => ['use' => '1', 'pages' => 'alle', 'copies' => '2', 'color' => 'bw', 'per_sheet' => '1']]);
    $content = $plans->render($seminar, $items);

    expect(substr($content, 0, 4))->toBe('%PDF')->and(sfPageCount($content))->toBe(1 + 6 + 2)->and($plans->totals($seminar, $items))->toBe(['bw' => 6, 'color' => 2, 'total' => 8]);
});

it('starts every copy on a front side when printing double sided', function () {
    [$seminar, $anna] = sfSeminar();
    $a = app(SeminarDocuments::class)->add($seminar, sfPdf('a.pdf', 3), 'material', null, $anna);
    $plans = app(PrintPlans::class);

    $content = $plans->render($seminar, $plans->normalize($seminar, [$a->id => ['use' => '1', 'pages' => 'alle', 'copies' => '2', 'color' => 'bw', 'per_sheet' => '1', 'duplex' => '1']]));

    expect(sfPageCount($content))->toBe(1 + 2 * 4);
});

it('lets the lead, the confirmed team and the coordination use the planner, nobody else', function () {
    [$seminar, $anna, $ben, $carl] = sfSeminar();
    app(SeminarDocuments::class)->add($seminar, sfPdf('material.pdf', 2), 'material', null, $anna);

    $this->get(route('seminars.files.planner', $seminar))->assertRedirect(route('login'));

    foreach ([$anna, $ben, sfUser(RoleKey::Coordination), sfUser(RoleKey::AdministrationStaff)] as $user) {
        $this->actingAs($user)->get(route('seminars.files.planner', $seminar))->assertOk()->assertSee('material.pdf');
    }

    foreach ([$carl, sfUser(RoleKey::Team), sfUser(RoleKey::Member), sfUser(RoleKey::School)] as $user) {
        $this->actingAs($user)->get(route('seminars.files.planner', $seminar))->assertNotFound();
    }
});

it('saves the plan and delivers the pdf and the folder zip', function () {
    [$seminar, $anna, $ben] = sfSeminar();
    $doc = app(SeminarDocuments::class)->add($seminar, sfPdf('material.pdf', 2), 'material', 'Arbeitsblatt', $anna);
    app(SeminarDocuments::class)->add($seminar, sfPdf('vertrag.pdf', 1), 'team_contract', null, $anna);
    $this->actingAs($ben);

    $this->get(route('seminars.files.pdf', $seminar))->assertRedirect(route('seminars.files.planner', $seminar))->assertSessionHasErrors('plan');
    $this->put(route('seminars.files.save', $seminar), ['rows' => []])->assertSessionHasErrors('plan');
    $this->put(route('seminars.files.save', $seminar), ['rows' => [$doc->id => ['use' => '1', 'pages' => 'alle', 'copies' => '25', 'color' => 'bw', 'per_sheet' => '1']]])->assertSessionHas('success');

    $this->get(route('seminars.files.planner', $seminar))->assertOk()->assertSee('Zusammenfassung')->assertSee('50');

    $response = $this->get(route('seminars.files.pdf', $seminar))->assertOk();
    expect($response->headers->get('Content-Type'))->toBe('application/pdf')->and(sfPageCount($response->getContent()))->toBe(1 + 50);

    $zipFile = $this->get(route('seminars.files.folder', $seminar))->assertOk()->baseResponse->getFile()->getPathname();
    $zip = new ZipArchive;
    $zip->open($zipFile);
    $names = [];

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $names[] = (string) $zip->getNameIndex($i);
    }

    expect($names)->toContain('Inhaltsverzeichnis.txt')->and(implode(' ', $names))->toContain('material.pdf')->and(implode(' ', $names))->not->toContain('vertrag');
    expect((string) $zip->getFromName('Inhaltsverzeichnis.txt'))->toContain($seminar->number);
    $zip->close();
});
