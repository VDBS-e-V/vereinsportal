<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Mailings\Jobs\SendMailing;
use App\Modules\Mailings\Mail\MailingMail;
use App\Modules\Mailings\Models\Mailing;
use App\Modules\Mailings\Models\MailingAttachment;
use App\Modules\Mailings\Models\MailSnippet;
use App\Modules\Mailings\Services\Mailings;
use App\Modules\Mailings\Support\MailAccessibilityCheck;
use App\Modules\Mailings\Support\MailPlaceholders;
use App\Modules\Mailings\Support\MailText;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

function ctUser(RoleKey $role, array $person = []): User
{
    $p = Person::factory()->create($person);
    $user = User::factory()->create(['person_id' => $p->id, 'email' => $p->email]);
    RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);

    return $user;
}

function ctDraft($test): Mailing
{
    $test->post(route('mailings.office.draft'), ['groups' => ['team'], 'subject' => 'Hinweis', 'body' => '<p>Hallo</p>']);

    return Mailing::query()->latest('id')->firstOrFail();
}

it('uses a default value only for empty values and keeps the placeholder check strict', function () {
    expect(MailPlaceholders::render('Hallo {nachname|Frau/Herr}', ['nachname' => ''], false))->toBe('Hallo Frau/Herr')
        ->and(MailPlaceholders::render('Hallo {nachname|Frau/Herr}', ['nachname' => 'Muster'], false))->toBe('Hallo Muster')
        ->and(MailPlaceholders::render('Ort: {ort|noch offen}', ['ort' => ''], true))->toBe('Ort: noch offen')
        ->and(MailPlaceholders::render('Ort: {ort|<b>}', ['ort' => ''], true))->toBe('Ort: &lt;b&gt;')
        ->and(MailPlaceholders::render('Hallo {nachname}', ['nachname' => ''], false))->toBe('Hallo ')
        ->and(MailPlaceholders::used('{vorname|Mitglied} und {vorname}'))->toBe(['vorname'])
        ->and(MailPlaceholders::unknown('{tippfehler|x} {name|y}', 'general'))->toBe(['tippfehler'])
        ->and(MailPlaceholders::render('{unbekannt|x}', [], false))->toBe('{unbekannt|x}');
});

it('makes a text version with link addresses and no tags', function () {
    $html = '<h2>Einladung</h2><p>Bitte <a href="https://beispiel.example/anmeldung">Anmeldung</a> nutzen.</p><ul><li>Punkt eins</li><li>Punkt zwei</li></ul><p><a href="https://beispiel.example">https://beispiel.example</a></p>';
    $text = MailText::fromHtml($html);

    expect($text)->toContain('Einladung')->toContain('Anmeldung (https://beispiel.example/anmeldung)')->toContain("- Punkt eins\n- Punkt zwei")
        ->and(substr_count($text, 'https://beispiel.example'))->toBe(2)
        ->and($text)->not->toContain('<');
});

it('sends the text version together with the html', function () {
    $mail = new MailingMail('Betreff', '<p>Siehe <a href="https://beispiel.example/a">Anmeldung</a></p>', 'internal');

    $mail->assertSeeInText('Anmeldung (https://beispiel.example/a)');
    $mail->assertSeeInHtml('Anmeldung');
});

it('finds vague link texts, empty and skipped headings and long paragraphs but nothing in a clean text', function () {
    $clean = '<h2>Einladung</h2><p>Hier steht der <a href="https://x.example">Link zu den Unterlagen</a>.</p><h3>Ablauf</h3><p>Kurz.</p>';
    $bad = '<h2>Titel</h2><p><a href="https://x.example">hier</a> und <a href="https://y.example">Klicken Sie hier!</a></p><h4></h4><p>'.str_repeat('wort ', 130).'</p>';

    $hints = MailAccessibilityCheck::check($bad);

    expect(MailAccessibilityCheck::check($clean))->toBe([])
        ->and(implode(' ', $hints))->toContain('„hier“')->toContain('Klicken Sie hier')->toContain('Überschrift ist leer')->toContain('springen von 2 auf 4')->toContain('mehr als 120 Wörter');
});

it('shows the hints in the preview without blocking', function () {
    Mail::fake();
    ctUser(RoleKey::Team);
    $this->actingAs(ctUser(RoleKey::AdministrationStaff));

    $this->post(route('mailings.office.preview'), ['groups' => ['team'], 'subject' => 'Hinweis', 'body' => '<p><a href="https://x.example">hier</a></p>'])->assertSessionHas('preview')->assertSessionHas('hints');
});

it('keeps snippets with the people who manage templates and offers them when writing', function () {
    $staff = ctUser(RoleKey::AdministrationStaff);
    $this->actingAs($staff);

    $this->post(route('mailings.templates.snippets.store'), ['name' => 'Anmeldehinweis', 'body' => '<p>Bitte melden Sie sich bis {frist|bald} an.</p>'])->assertRedirect(route('mailings.templates.snippets'));
    $snippet = MailSnippet::query()->firstOrFail();

    $this->post(route('mailings.templates.snippets.store'), ['name' => 'Anmeldehinweis', 'body' => '<p>Doppelt</p>'])->assertSessionHasErrors('name');
    $this->post(route('mailings.templates.snippets.store'), ['name' => 'Kaputt', 'body' => '<p>{tippfehler}</p>'])->assertSessionHasErrors('body');
    $this->post(route('mailings.templates.snippets.store'), ['snippet_id' => $snippet->id, 'name' => 'Anmeldehinweis neu', 'body' => '<p>Neu</p>'])->assertRedirect();
    expect($snippet->fresh()->name)->toBe('Anmeldehinweis neu');

    $this->get(route('mailings.templates.snippets'))->assertOk()->assertSee('Anmeldehinweis neu');
    $this->get(route('mailings.office.create'))->assertOk()->assertSee('Textbaustein einfügen')->assertSee('data-snippet-select', false)->assertSee('Anmeldehinweis neu');

    $this->delete(route('mailings.templates.snippets.destroy', $snippet))->assertRedirect();
    expect(MailSnippet::query()->count())->toBe(0);

    $this->actingAs(ctUser(RoleKey::Member))->get(route('mailings.templates.snippets'))->assertForbidden();
});

it('adds, lists, downloads and removes attachments only on the own draft and within the limits', function () {
    Storage::fake('local');
    config(['portal.mailings.attachments.max_kb' => 150]);
    $author = ctUser(RoleKey::AdministrationStaff);
    $this->actingAs($author);
    $draft = ctDraft($this);

    $this->post(route('mailings.office.attachments.store', $draft), ['attachment' => UploadedFile::fake()->create('programm.pdf', 100, 'application/pdf')])->assertRedirect()->assertSessionHas('success');
    $attachment = MailingAttachment::query()->firstOrFail();
    Storage::disk('local')->assertExists($attachment->path);
    expect($attachment->original_name)->toBe('programm.pdf');

    $this->post(route('mailings.office.attachments.store', $draft), ['attachment' => UploadedFile::fake()->create('zweite.pdf', 100, 'application/pdf')])->assertSessionHas('error');
    $this->post(route('mailings.office.attachments.store', $draft), ['attachment' => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')])->assertSessionHasErrors('attachment');
    expect(MailingAttachment::query()->count())->toBe(1);

    $this->get(route('mailings.office.create', ['entwurf' => $draft->id]))->assertOk()->assertSee('programm.pdf');
    $this->get(route('mailings.office.attachments.download', [$draft, $attachment]))->assertOk()->assertDownload('programm.pdf');

    $other = ctUser(RoleKey::AdministrationStaff);
    $this->actingAs($other)->get(route('mailings.office.attachments.download', [$draft, $attachment]))->assertForbidden();
    $this->actingAs($other)->post(route('mailings.office.attachments.store', $draft), ['attachment' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])->assertNotFound();
    $this->actingAs($other)->delete(route('mailings.office.attachments.destroy', [$draft, $attachment]))->assertNotFound();

    $this->actingAs($author)->delete(route('mailings.office.attachments.destroy', [$draft, $attachment]))->assertRedirect();
    expect(MailingAttachment::query()->count())->toBe(0);
    Storage::disk('local')->assertMissing($attachment->path);
});

it('sends the attachments with every mail and removes them with the draft and after the retention period', function () {
    Storage::fake('local');
    Mail::fake();
    Queue::fake();
    ctUser(RoleKey::Team, ['email' => 'tina@beispiel.example']);
    $this->actingAs(ctUser(RoleKey::AdministrationStaff));
    $draft = ctDraft($this);
    $this->post(route('mailings.office.attachments.store', $draft), ['attachment' => UploadedFile::fake()->create('programm.pdf', 20, 'application/pdf')]);

    $this->post(route('mailings.office.store'), ['draft_id' => $draft->id, 'groups' => ['team'], 'subject' => 'Hinweis', 'body' => '<p>Hallo</p>'])->assertRedirect();
    (new SendMailing($draft->id))->handle(app(Mailings::class));

    Mail::assertSent(MailingMail::class, fn (MailingMail $mail): bool => count($mail->attachments()) === 1 && $mail->attachmentFiles[0]['name'] === 'programm.pdf');

    $attachment = MailingAttachment::query()->firstOrFail();
    $draft->fresh()->forceFill(['status' => 'done', 'finished_at' => now()->subDays(100)])->save();
    $this->artisan('mailings:prune')->assertSuccessful();

    expect(MailingAttachment::query()->count())->toBe(0);
    Storage::disk('local')->assertMissing($attachment->path);
});

it('deletes the attachments with a draft', function () {
    Storage::fake('local');
    $this->actingAs(ctUser(RoleKey::AdministrationStaff));
    $draft = ctDraft($this);
    $this->post(route('mailings.office.attachments.store', $draft), ['attachment' => UploadedFile::fake()->create('programm.pdf', 20, 'application/pdf')]);
    $path = MailingAttachment::query()->firstOrFail()->path;

    $this->delete(route('mailings.office.destroy', $draft))->assertRedirect();

    expect(MailingAttachment::query()->count())->toBe(0);
    Storage::disk('local')->assertMissing($path);
});
