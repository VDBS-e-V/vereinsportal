<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\Contacts\Models\TeamContact;
use App\Modules\People\Models\Person;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use App\Modules\Seminars\Models\SeminarClosure;
use App\Modules\Seminars\Models\SeminarDocument;
use App\Modules\Seminars\Services\SeminarAdministration;
use App\Modules\Seminars\Services\SeminarClosures;
use App\Modules\Seminars\Services\SeminarDocuments;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Storage::fake('local');
});

function sdUser(?RoleKey $role = RoleKey::Coordination): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

function sdSeminar(SeminarStatus $status = SeminarStatus::Done): Seminar
{
    $seminar = Seminar::factory()->create(['status' => $status, 'expected_participants' => 40]);
    app(SeminarAdministration::class)->addSession($seminar, ['date' => now()->subMonth()->toDateString(), 'start' => '09:00', 'end' => '15:00'], sdUser());

    return $seminar;
}

function sdPdf(string $name = 'vertrag.pdf', string $text = 'Inhalt'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n%".$text."\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n");
}

function sdUpload($test, Seminar $seminar, array $files, string $kind = 'other', array $extra = [])
{
    return $test->post(route('seminars.documents.store', $seminar), array_merge(['kind' => $kind, 'files' => $files], $extra));
}

function sdClose(Seminar $seminar, User $by): SeminarClosure
{
    return app(SeminarClosures::class)->close($seminar, [], ['quote' => 'k', 'invoice' => 'k', 'team' => 'k', 'evaluation' => 'k', 'payments' => 'k'], $by);
}

it('stores a pdf privately with kind, title and retention and logs no file name', function () {
    $seminar = sdSeminar();
    $this->actingAs(sdUser());

    sdUpload($this, $seminar, [sdPdf('geheimer-name.pdf')], 'invoice', ['title' => 'Rechnung Mai'])->assertRedirect(route('seminars.show', $seminar))->assertSessionHas('success');

    $document = SeminarDocument::query()->first();
    expect($document->kind)->toBe('invoice')->and($document->title)->toBe('Rechnung Mai')->and($document->path)->toStartWith('seminars/'.$seminar->id.'/')->and($document->path)->not->toContain('geheimer')
        ->and($document->mime)->toBe('application/pdf')->and($document->retention_until->isAfter(now()->addYears(9)))->toBeTrue();
    Storage::disk('local')->assertExists($document->path);

    $entry = AuditEntry::query()->where('event', 'seminar.document_added')->firstOrFail();
    expect(json_encode($entry->context))->not->toContain('geheimer')->and($entry->context['nummer'])->toBe($seminar->number);
});

it('refuses photos and pictures, also when they are named like a pdf', function () {
    $seminar = sdSeminar();
    $this->actingAs(sdUser());
    $gif = "GIF89a\x01\x00\x01\x00\x00\x00\x00;";
    $jpeg = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00\xFF\xD9";

    foreach ([['foto.jpg', $jpeg], ['gruppe.png', $gif], ['getarnt.pdf', $gif], ['getarnt2.pdf', $jpeg], ['bild.heic', 'xxxx']] as [$name, $content]) {
        sdUpload($this, $seminar, [UploadedFile::fake()->createWithContent($name, $content)])->assertSessionHasErrors('documents');
    }

    expect(SeminarDocument::query()->count())->toBe(0);
});

it('refuses programs, unknown types and files whose content does not fit the ending', function () {
    $seminar = sdSeminar();
    $this->actingAs(sdUser());

    foreach ([['setup.exe', 'MZ'], ['skript.php', '<?php echo 1;'], ['seite.html', '<html></html>'], ['falsch.pdf', 'nur Text, kein PDF']] as [$name, $content]) {
        sdUpload($this, $seminar, [UploadedFile::fake()->createWithContent($name, $content)])->assertSessionHasErrors('documents');
    }

    expect(SeminarDocument::query()->count())->toBe(0);
});

it('accepts text and csv and several files at once with the good ones despite a bad one', function () {
    $seminar = sdSeminar();
    $this->actingAs(sdUser());

    sdUpload($this, $seminar, [sdPdf('a.pdf'), UploadedFile::fake()->createWithContent('b.csv', "a;b\n1;2\n"), UploadedFile::fake()->createWithContent('c.txt', 'Hallo'), UploadedFile::fake()->createWithContent('d.jpg', "\xFF\xD8\xFF\xE0\x00\x10JFIF")])
        ->assertSessionHas('success')->assertSessionHasErrors('documents');

    expect(SeminarDocument::query()->count())->toBe(3);
});

it('keeps the documents for the coordination, the office and the board', function () {
    $seminar = sdSeminar();

    sdUpload($this, $seminar, [sdPdf()])->assertRedirect(route('login'));

    foreach ([RoleKey::Team, RoleKey::School, RoleKey::Member, RoleKey::EducationCoordination, null] as $role) {
        $this->actingAs(sdUser($role));
        sdUpload($this, $seminar, [sdPdf()])->assertForbidden();
        $this->get(route('seminars.folder', $seminar))->assertForbidden();
    }

    foreach ([RoleKey::Coordination, RoleKey::AdministrationStaff, RoleKey::BoardMember] as $role) {
        $this->actingAs(sdUser($role));
        sdUpload($this, $seminar, [sdPdf()])->assertSessionHas('success');
    }

    expect(SeminarDocument::query()->count())->toBe(3);
});

it('enforces the size of a file and of all files together', function () {
    $seminar = sdSeminar();
    $this->actingAs(sdUser());
    config(['portal.seminars.documents.max_kb' => 1]);
    sdUpload($this, $seminar, [UploadedFile::fake()->createWithContent('gross.txt', str_repeat('x', 2048))])->assertSessionHasErrors('documents');

    config(['portal.seminars.documents.max_kb' => 100, 'portal.seminars.documents.max_total_kb' => 3]);
    sdUpload($this, $seminar, [UploadedFile::fake()->createWithContent('eins.txt', str_repeat('x', 2048))])->assertSessionHas('success');
    sdUpload($this, $seminar, [UploadedFile::fake()->createWithContent('zwei.txt', str_repeat('x', 2048))])->assertSessionHasErrors('documents');

    expect(SeminarDocument::query()->count())->toBe(1);
});

it('delivers a document only to the permitted and only through its own seminar', function () {
    $seminar = sdSeminar();
    $other = sdSeminar();
    $this->actingAs(sdUser());
    sdUpload($this, $seminar, [sdPdf('x.pdf', 'geheim')]);
    $document = SeminarDocument::query()->first();

    $response = $this->get(route('seminars.documents.download', [$seminar, $document]))->assertOk();
    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
    $this->get(route('seminars.documents.download', [$other, $document]))->assertNotFound();

    $this->actingAs(sdUser(RoleKey::Team))->get(route('seminars.documents.download', [$seminar, $document]))->assertForbidden();
});

it('deletes several documents at once and removes their files', function () {
    $seminar = sdSeminar();
    $this->actingAs(sdUser());
    sdUpload($this, $seminar, [sdPdf('a.pdf', '1'), sdPdf('b.pdf', '2'), sdPdf('c.pdf', '3')]);
    $paths = SeminarDocument::query()->pluck('path')->all();
    $ids = SeminarDocument::query()->orderBy('id')->limit(2)->pluck('id')->all();

    $this->post(route('seminars.documents.destroy', $seminar), [])->assertSessionHasErrors('ids');
    $this->post(route('seminars.documents.destroy', $seminar), ['ids' => $ids])->assertSessionHas('success');

    expect(SeminarDocument::query()->count())->toBe(1);
    Storage::disk('local')->assertMissing($paths[0]);
    Storage::disk('local')->assertExists(SeminarDocument::query()->value('path'));
    expect(AuditEntry::query()->where('event', 'seminar.document_removed')->count())->toBe(2);
});

it('shows the documents on the seminar page only to those who may see them', function () {
    $seminar = sdSeminar();
    $this->actingAs(sdUser());
    $this->get(route('seminars.show', $seminar))->assertOk()->assertSee('Keine Fotos von Teilnehmenden')->assertSee('Noch keine Dokumente abgelegt')->assertDontSee('data-vdbs-select-all="documents-form"', false);

    sdUpload($this, $seminar, [sdPdf()]);
    $this->get(route('seminars.show', $seminar))->assertOk()->assertSee('data-vdbs-select-all="documents-form"', false);
    $this->actingAs(sdUser(RoleKey::EducationCoordination))->get(route('seminars.show', $seminar))->assertOk()->assertDontSee('Keine Fotos von Teilnehmenden');
});

it('shows the checklist with automatic points and refuses a closure without reasons', function () {
    $seminar = sdSeminar();
    $coordination = sdUser();
    $this->actingAs($coordination);

    $this->get(route('seminars.closure', $seminar))->assertOk()->assertSee('Checkliste')->assertSee('Kostenvoranschlag liegt vor');
    $this->post(route('seminars.closure.store', $seminar), [])->assertSessionHasErrors('closure');
    expect($seminar->fresh()->status)->toBe(SeminarStatus::Done);

    sdUpload($this, $seminar, [sdPdf('r.pdf')], 'invoice');
    $checklist = app(SeminarClosures::class)->checklist($seminar->fresh());
    expect($checklist['invoice']['auto'])->toBeTrue()->and($checklist['quote']['auto'])->toBeFalse()->and($checklist['evaluation']['auto'])->toBeFalse();
});

it('closes with confirmations and reasons, keeps numbers only in the snapshot and writes the status', function () {
    $seminar = sdSeminar();
    $contact = TeamContact::query()->create(['email' => 'team@example.org']);
    SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'contact_id' => $contact->id, 'status' => 'confirmed', 'confirmed_at' => now(), 'guest_name' => 'Geheimer Teamname']);
    $this->actingAs(sdUser());
    sdUpload($this, $seminar, [sdPdf('a.pdf')], 'evaluation');

    $this->post(route('seminars.closure.store', $seminar), ['done' => ['payments' => '1'], 'skip_reason' => ['quote' => 'Mündlich vereinbart', 'invoice' => 'Ehrenamtlich']])->assertRedirect(route('seminars.show', $seminar))->assertSessionHas('success');

    $closure = SeminarClosure::query()->first();
    expect($seminar->fresh()->status)->toBe(SeminarStatus::Completed)
        ->and($closure->checklist['team']['state'])->toBe('auto')->and($closure->checklist['evaluation']['state'])->toBe('auto')
        ->and($closure->checklist['payments']['state'])->toBe('confirmed')->and($closure->checklist['quote'])->toMatchArray(['state' => 'skipped', 'reason' => 'Mündlich vereinbart'])
        ->and($closure->snapshot)->toMatchArray(['sessions' => 1, 'hours' => 6.0, 'team_size' => 1, 'expected_participants' => 40])
        ->and(json_encode($closure->snapshot))->not->toContain('Geheimer Teamname')->and(json_encode($closure->snapshot))->not->toContain('team@example.org')
        ->and(AuditEntry::query()->where('event', 'seminar.closed')->count())->toBe(1);
});

it('lets only durchgeführte seminars be closed and sends the status change to the closure page', function () {
    $this->actingAs(sdUser());
    $planned = sdSeminar(SeminarStatus::Confirmed);

    $this->get(route('seminars.closure', $planned))->assertRedirect(route('seminars.show', $planned));
    $this->post(route('seminars.closure.store', $planned), ['skip_reason' => ['quote' => 'x', 'invoice' => 'x', 'team' => 'x', 'evaluation' => 'x', 'payments' => 'x']]);
    expect($planned->fresh()->status)->toBe(SeminarStatus::Confirmed);

    $done = sdSeminar();
    $this->post(route('seminars.status', $done), ['status' => 'completed'])->assertRedirect(route('seminars.closure', $done));
    expect($done->fresh()->status)->toBe(SeminarStatus::Done);
});

it('protects contracts, quotes and invoices after the closure and lets only the board delete them with a reason', function () {
    $seminar = sdSeminar();
    $coordination = sdUser();
    $this->actingAs($coordination);
    sdUpload($this, $seminar, [sdPdf('v.pdf')], 'contract');
    sdUpload($this, $seminar, [sdPdf('s.pdf', 's')], 'other');
    sdClose($seminar->fresh(), $coordination);
    $contract = SeminarDocument::query()->where('kind', 'contract')->first();
    $other = SeminarDocument::query()->where('kind', 'other')->first();

    $this->post(route('seminars.documents.destroy', $seminar), ['ids' => [$contract->id]])->assertSessionHasErrors('documents');
    sdUpload($this, $seminar, [sdPdf('neu.pdf', 'n')], 'invoice')->assertSessionHasErrors('documents');
    expect(SeminarDocument::query()->whereKey($contract->id)->exists())->toBeTrue();

    $this->post(route('seminars.documents.destroy', $seminar), ['ids' => [$other->id]])->assertSessionHas('success');

    $this->actingAs(sdUser(RoleKey::BoardMember));
    $this->post(route('seminars.documents.destroy', $seminar), ['ids' => [$contract->id]])->assertSessionHasErrors('documents');
    $this->post(route('seminars.documents.destroy', $seminar), ['ids' => [$contract->id], 'reason' => 'Falsche Datei'])->assertSessionHas('success');
    expect(SeminarDocument::query()->whereKey($contract->id)->exists())->toBeFalse()
        ->and(json_encode(AuditEntry::query()->where('event', 'seminar.document_removed')->latest('id')->first()->context))->toContain('Falsche Datei');
});

it('lets only the board reopen with a reason and keeps the old closure as a version', function () {
    $seminar = sdSeminar();
    $coordination = sdUser();
    sdClose($seminar, $coordination);

    $this->actingAs($coordination)->post(route('seminars.closure.reopen', $seminar), ['reason' => 'Fehler'])->assertForbidden();

    $board = sdUser(RoleKey::BoardMember);
    $this->actingAs($board)->post(route('seminars.closure.reopen', $seminar), [])->assertSessionHasErrors('reason');
    $this->post(route('seminars.closure.reopen', $seminar), ['reason' => 'Abrechnung fehlte'])->assertSessionHas('success');

    $closure = SeminarClosure::query()->first();
    expect($seminar->fresh()->status)->toBe(SeminarStatus::Done)->and($closure->reopened_at)->not->toBeNull()->and($closure->reopen_reason)->toBe('Abrechnung fehlte')
        ->and($seminar->fresh()->currentClosure())->toBeNull();

    sdClose($seminar->fresh(), $board);
    expect(SeminarClosure::query()->count())->toBe(2)->and($seminar->fresh()->currentClosure()->id)->not->toBe($closure->id);
});

it('builds the folder as a zip with every document, a content list with checksums and the snapshot', function () {
    if (! class_exists(ZipArchive::class)) {
        $this->markTestSkipped('Die PHP-Erweiterung zip fehlt.');
    }

    $seminar = sdSeminar();
    $coordination = sdUser();
    $this->actingAs($coordination);
    sdUpload($this, $seminar, [sdPdf('gleich.pdf', 'eins')], 'invoice');
    sdUpload($this, $seminar, [sdPdf('gleich.pdf', 'zwei')], 'invoice');
    sdUpload($this, $seminar, [UploadedFile::fake()->createWithContent('liste.csv', "a;b\n")], 'participants');
    sdClose($seminar->fresh(), $coordination);

    $response = $this->get(route('seminars.folder', $seminar))->assertOk();
    $zip = new ZipArchive;
    expect($zip->open($response->baseResponse->getFile()->getPathname()))->toBeTrue();
    $names = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $names[] = $zip->getNameIndex($i);
    }
    $content = $zip->getFromName(collect($names)->first(fn ($n) => str_ends_with($n, 'INHALT.txt')));

    expect($names)->toHaveCount(7)->and(collect($names)->filter(fn ($n) => str_contains($n, '/invoice/'))->count())->toBe(2)
        ->and(collect($names)->contains(fn ($n) => str_ends_with($n, 'Abschluss-Schnappschuss.html')))->toBeTrue()
        ->and($content)->toContain(hash('sha256', Storage::disk('local')->get(SeminarDocument::query()->where('kind', 'participants')->value('path'))))->and($content)->toContain($seminar->title);
    $zip->close();
    expect(AuditEntry::query()->where('event', 'seminar.folder_downloaded')->count())->toBe(1);
});

it('refuses a folder that is larger than the limit', function () {
    $seminar = sdSeminar();
    $this->actingAs(sdUser());
    sdUpload($this, $seminar, [UploadedFile::fake()->createWithContent('a.txt', str_repeat('x', 3000))]);
    config(['portal.seminars.documents.folder_max_mb' => 0]);
    SeminarDocument::query()->update(['size' => 2 * 1048576]);

    $this->get(route('seminars.folder', $seminar))->assertRedirect(route('seminars.show', $seminar))->assertSessionHasErrors('documents');
});

it('deletes documents only after the retention period and counts in a dry run', function () {
    $seminar = sdSeminar();
    $this->actingAs(sdUser());
    sdUpload($this, $seminar, [sdPdf('alt.pdf', 'a')]);
    sdUpload($this, $seminar, [sdPdf('neu.pdf', 'b')]);
    $old = SeminarDocument::query()->orderBy('id')->first();
    $old->forceFill(['retention_until' => now()->subDay()->toDateString()])->save();
    $oldPath = $old->path;

    $this->artisan('seminars:prune-documents', ['--dry-run' => true])->assertSuccessful();
    expect(SeminarDocument::query()->count())->toBe(2);

    $this->artisan('seminars:prune-documents')->assertSuccessful();
    expect(SeminarDocument::query()->count())->toBe(1);
    Storage::disk('local')->assertMissing($oldPath);
});

it('sets longer retention for contracts, quotes and invoices than for the rest', function () {
    $seminar = sdSeminar();
    $service = app(SeminarDocuments::class);

    expect($service->retentionFor($seminar, 'invoice')->diffInYears($service->retentionFor($seminar, 'other'), true))->toBeGreaterThanOrEqual(6.9);
});

it('schedules the pruning daily and offers it in the system tasks', function () {
    expect(file_get_contents(base_path('routes/console.php')))->toContain("Schedule::command('seminars:prune-documents')->daily()")
        ->and(app(\App\Modules\System\Services\SystemTasks::class)->find('seminardokumente-aufraeumen')['command'])->toBe('seminars:prune-documents');
});
