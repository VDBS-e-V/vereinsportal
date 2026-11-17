<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\People\Models\Person;
use App\Modules\Seminars\Enums\ApplicationStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use App\Modules\Seminars\Services\PrintPlans;
use App\Modules\Seminars\Support\PdfPages;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

function ppUser(RoleKey $role): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email, 'email_verified_at' => now()]);
    RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);

    return $user;
}

function ppDocument(Seminar $seminar, string $kind, string $title, ?int $pages): int
{
    return $seminar->documents()->create(['kind' => $kind, 'title' => $title, 'original_name' => $title.'.pdf', 'path' => 'seminars/'.$seminar->id.'/'.$title.'.pdf', 'mime' => 'application/pdf', 'size' => 100, 'retention_until' => now()->addYears(3)->toDateString(), 'uploaded_by_user_id' => ppUser(RoleKey::AdministrationStaff)->id, 'pages' => $pages])->id;
}

it('counts the pages of a pdf without a library', function () {
    $path = tempnam(sys_get_temp_dir(), 'pdf');
    file_put_contents($path, "%PDF-1.4\n1 0 obj << /Type /Pages /Kids [2 0 R 3 0 R] /Count 2 >> endobj\n2 0 obj << /Type /Page /Parent 1 0 R >> endobj\n3 0 obj << /Type /Page /Parent 1 0 R >> endobj\n%%EOF");
    file_put_contents($path.'x', 'kein pdf');

    expect(PdfPages::count($path))->toBe(2)->and(PdfPages::count($path.'x'))->toBeNull()->and(PdfPages::count('/nicht/da'))->toBeNull();

    unlink($path);
    unlink($path.'x');
});

it('reads page selections and rejects wrong ones', function () {
    $plans = app(PrintPlans::class);

    expect($plans->selected('', 10))->toBe(10)->and($plans->selected('alle', 10))->toBe(10)->and($plans->selected('1-3, 5', 10))->toBe(4)->and($plans->selected('2, 2, 3-4', 10))->toBe(3);
    expect(fn () => $plans->selected('0-3', 10))->toThrow(DomainException::class)
        ->and(fn () => $plans->selected('5-12', 10))->toThrow(DomainException::class)
        ->and(fn () => $plans->selected('drei', 10))->toThrow(DomainException::class);
});

it('calculates pages and sheets per color, pages per sheet and duplex', function () {
    $seminar = Seminar::factory()->create();
    $a = ppDocument($seminar, 'report', 'Ablauf', 10);
    $b = ppDocument($seminar, 'other', 'Plakat', 3);
    $coordination = ppUser(RoleKey::Coordination);

    $summary = app(PrintPlans::class)->save($seminar, $coordination, [
        $a => ['use' => '1', 'pages' => '1-6', 'copies' => '4', 'color' => 'bw', 'per_sheet' => '2', 'duplex' => '1'],
        $b => ['use' => '1', 'pages' => '', 'copies' => '2', 'color' => 'color', 'per_sheet' => '1'],
    ]);

    // Ablauf: 6 Seiten, 2 pro Blatt = 3 Seiten, beidseitig = 2 Blätter je Exemplar, 4 Exemplare = 8 Blätter. Plakat: 3 Blätter, 2 Exemplare = 6.
    expect($summary['totals'])->toBe(['pages_bw' => 24, 'pages_color' => 6, 'sheets_bw' => 8, 'sheets_color' => 6, 'sheets' => 14]);
});

it('asks for the page count when the portal could not read it', function () {
    $seminar = Seminar::factory()->create();
    $id = ppDocument($seminar, 'other', 'Scan', null);
    $user = ppUser(RoleKey::Coordination);

    expect(fn () => app(PrintPlans::class)->save($seminar, $user, [$id => ['use' => '1', 'copies' => '1']]))->toThrow(DomainException::class, 'Seitenzahl');
    app(PrintPlans::class)->save($seminar, $user, [$id => ['use' => '1', 'copies' => '1', 'page_count' => '7']]);

    expect(app(PrintPlans::class)->plan($seminar)->items[0]['page_count'])->toBe(7);
});

it('lets the coordination plan everything and shows team members only the documents that are not protected', function () {
    $seminar = Seminar::factory()->create();
    ppDocument($seminar, 'report', 'Ablauf', 4);
    ppDocument($seminar, 'contract', 'Vertrag Schule', 2);
    $member = ppUser(RoleKey::Team);
    SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'user_id' => $member->id, 'status' => ApplicationStatus::Confirmed]);

    $this->actingAs(ppUser(RoleKey::Team))->get(route('seminar-print.show', $seminar))->assertForbidden();

    $this->actingAs($member)->get(route('seminar-print.show', $seminar))->assertOk()->assertSee('Ablauf')->assertDontSee('Vertrag Schule');
    $this->actingAs(ppUser(RoleKey::Coordination))->get(route('seminar-print.show', $seminar))->assertOk()->assertSee('Ablauf')->assertSee('Vertrag Schule');
});

it('saves the plan through the form and shows the print list', function () {
    $seminar = Seminar::factory()->create(['number' => 'S-2027-0001']);
    $id = ppDocument($seminar, 'report', 'Ablauf', 4);
    $coordination = ppUser(RoleKey::Coordination);

    $this->actingAs($coordination)->get(route('seminar-print.list', $seminar))->assertNotFound();
    $this->actingAs($coordination)->put(route('seminar-print.update', $seminar), ['items' => [$id => ['use' => '1', 'pages' => '1-9', 'copies' => '1']]])->assertSessionHasErrors('print');
    $this->actingAs($coordination)->put(route('seminar-print.update', $seminar), ['items' => [$id => ['use' => '1', 'pages' => '1-2', 'copies' => '3', 'color' => 'color', 'per_sheet' => '1']]])->assertSessionHas('success');

    $this->actingAs($coordination)->get(route('seminar-print.show', $seminar))->assertOk()->assertSee('Blätter insgesamt');
    $this->actingAs($coordination)->get(route('seminar-print.list', $seminar))->assertOk()->assertSee('Druckliste S-2027-0001')->assertSee('Farbe');
});
