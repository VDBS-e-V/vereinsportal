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
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Events\SeminarCompleted;
use App\Modules\Seminars\Mail\SeminarNoticeMail;
use App\Modules\Seminars\Models\PaymentOrder;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Storage::fake('local');
    Mail::fake();
});

function rpUser(?RoleKey $role = null, string $first = 'Test'): User
{
    $person = Person::factory()->create(['first_name' => $first]);
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

function rpFile(string $name = 'bericht.pdf'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n");
}

/** Vorbereitetes Seminar, Termin gestern; Leitung Anna und zugesagt Ben. */
function rpSeminar(SeminarStatus $status = SeminarStatus::Prepared): array
{
    $anna = rpUser(RoleKey::Team, 'Anna');
    $ben = rpUser(RoleKey::Team, 'Ben');
    $seminar = Seminar::factory()->status($status)->create(['lead_user_id' => $anna->id, 'contact_email' => 'lehrkraft@schule.example']);
    $seminar->sessions()->create(['starts_at' => now()->subDay()]);
    SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'user_id' => $anna->id, 'outcome' => 'confirmed', 'decision' => 'confirm']);
    SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'user_id' => $ben->id, 'outcome' => 'confirmed', 'decision' => 'confirm']);

    return [$seminar, $anna, $ben];
}

it('shows the lead only the own seminar', function () {
    [$seminar, $anna] = rpSeminar();
    $other = Seminar::factory()->status(SeminarStatus::Prepared)->create(['title' => 'Fremdes Seminar', 'lead_user_id' => rpUser(RoleKey::Team)->id]);

    $this->get(route('seminars.lead.index'))->assertRedirect(route('login'));
    $this->actingAs($anna)->get(route('seminars.lead.index'))->assertOk()->assertSee($seminar->title)->assertDontSee('Fremdes Seminar');
    $this->get(route('seminars.lead.show', $seminar))->assertOk()->assertSee('Durchführung bestätigen');
    $this->get(route('seminars.lead.show', $other))->assertNotFound();
    $this->post(route('seminars.lead.held', $other))->assertNotFound();
});

it('confirms the execution after the last session and sets the deadline', function () {
    [$seminar, $anna] = rpSeminar();
    $future = Seminar::factory()->status(SeminarStatus::Prepared)->create(['lead_user_id' => $anna->id]);
    $future->sessions()->create(['starts_at' => now()->addWeek()]);
    $this->actingAs($anna);

    $this->post(route('seminars.lead.held', $future))->assertSessionHasErrors('lead');
    expect($future->fresh()->status)->toBe(SeminarStatus::Prepared);

    $this->post(route('seminars.lead.held', $seminar))->assertSessionHas('success');

    $seminar->refresh();

    expect($seminar->status)->toBe(SeminarStatus::Held)->and($seminar->held_at)->not->toBeNull()->and($seminar->report_due_on->toDateString())->toBe(now()->subDay()->addDays(14)->toDateString());
});

it('turns an uploaded team contract into a payment order and tells the accounting', function () {
    [$seminar, $anna, $ben] = rpSeminar(SeminarStatus::Held);
    $treasury = rpUser(RoleKey::Treasury);
    $this->actingAs($anna);

    $this->post(route('seminars.lead.contract', $seminar), ['member' => $ben->id, 'amount' => 'viel', 'file' => rpFile('vertrag.pdf')])->assertSessionHasErrors('lead');
    $this->post(route('seminars.lead.contract', $seminar), ['member' => rpUser(RoleKey::Team)->id, 'amount' => '200', 'file' => rpFile('vertrag.pdf')])->assertSessionHasErrors('lead');
    expect(PaymentOrder::query()->count())->toBe(0);

    $this->post(route('seminars.lead.contract', $seminar), ['member' => $ben->id, 'amount' => '250,50', 'file' => rpFile('vertrag.pdf')])->assertSessionHas('success');

    $order = PaymentOrder::query()->firstOrFail();

    expect($order->amount_cents)->toBe(25050)->and($order->user_id)->toBe($ben->id)->and($order->status)->toBe('open')->and($order->document->kind)->toBe('team_contract')
        ->and(AuditEntry::query()->where('event', 'seminars.payment_created')->count())->toBe(1);
    Mail::assertSent(SeminarNoticeMail::class, fn (SeminarNoticeMail $mail): bool => $mail->hasTo($treasury->email) && str_starts_with($mail->subjectLine, 'Neuer Zahlungsauftrag'));

    // ein zweiter Upload ersetzt den offenen Auftrag, es bleibt einer
    $this->post(route('seminars.lead.contract', $seminar), ['member' => $ben->id, 'amount' => '300', 'file' => rpFile('vertrag2.pdf')])->assertSessionHas('success');
    expect(PaymentOrder::query()->count())->toBe(1)->and(PaymentOrder::query()->first()->amount_cents)->toBe(30000);
});

it('hands in the report only with the report file and all contracts', function () {
    [$seminar, $anna, $ben] = rpSeminar(SeminarStatus::Held);
    $this->actingAs($anna);

    $this->post(route('seminars.lead.submit', $seminar))->assertSessionHasErrors('lead');

    $this->post(route('seminars.lead.upload', $seminar), ['kind' => 'report', 'file' => rpFile()])->assertSessionHas('success');
    $this->post(route('seminars.lead.upload', $seminar), ['kind' => 'schulvertrag', 'file' => rpFile()])->assertSessionHasErrors('kind');
    $this->post(route('seminars.lead.submit', $seminar))->assertSessionHasErrors('lead');
    expect($seminar->fresh()->status)->toBe(SeminarStatus::Held);

    foreach ([$anna, $ben] as $member) {
        $this->post(route('seminars.lead.contract', $seminar), ['member' => $member->id, 'amount' => '100', 'file' => rpFile('vertrag.pdf')]);
    }

    $this->post(route('seminars.lead.submit', $seminar))->assertSessionHas('success');

    expect($seminar->fresh()->status)->toBe(SeminarStatus::Reported)->and($seminar->fresh()->reported_at)->not->toBeNull();
});

it('lets the coordination return the report, send results with a valid link and complete', function () {
    Event::fake([SeminarCompleted::class]);
    [$seminar] = rpSeminar(SeminarStatus::Reported);
    $seminar->documents()->create(['kind' => 'report', 'title' => 'Bericht', 'path' => 'seminars/x/b.pdf', 'original_name' => 'bericht.pdf', 'mime' => 'application/pdf', 'size' => 10]);
    $this->actingAs(rpUser(RoleKey::Coordination));

    $this->post(route('seminars.plan.complete', $seminar))->assertSessionHasErrors('team');
    $this->post(route('seminars.plan.send-results', $seminar), ['results_url' => 'http://unsicher.example', 'results_expires_on' => now()->addYears(2)->toDateString()])->assertSessionHasErrors('results_url');
    $this->post(route('seminars.plan.send-results', $seminar), ['results_url' => 'https://cloud.example/s/abc', 'results_expires_on' => now()->addMonths(3)->toDateString()])->assertSessionHasErrors('team');
    expect($seminar->fresh()->report_sent_at)->toBeNull();

    $this->post(route('seminars.plan.send-results', $seminar), ['results_url' => 'https://cloud.example/s/abc', 'results_expires_on' => now()->addYears(2)->toDateString()])->assertSessionHas('success');
    Mail::assertSent(SeminarNoticeMail::class, fn (SeminarNoticeMail $mail): bool => $mail->hasTo('lehrkraft@schule.example') && in_array('https://cloud.example/s/abc', $mail->paragraphs, true));

    $this->post(route('seminars.plan.complete', $seminar))->assertSessionHas('success');

    expect($seminar->fresh()->status)->toBe(SeminarStatus::Completed)->and($seminar->fresh()->completed_at)->not->toBeNull()->and(AuditEntry::query()->where('event', 'seminars.completed')->count())->toBe(1);
    Event::assertDispatched(SeminarCompleted::class);
});

it('sends a report back with a reason', function () {
    [$seminar] = rpSeminar(SeminarStatus::Reported);
    $this->actingAs(rpUser(RoleKey::Coordination));

    $this->post(route('seminars.plan.return-report', $seminar), ['comment' => ''])->assertSessionHasErrors('comment');
    $this->post(route('seminars.plan.return-report', $seminar), ['comment' => 'Teilnehmendenzahl fehlt'])->assertSessionHas('success');

    expect($seminar->fresh()->status)->toBe(SeminarStatus::Held)->and($seminar->events()->first()->comment)->toBe('Teilnehmendenzahl fehlt');
});

it('shows the accounting the orders, exports the open ones and records the decision', function () {
    [$seminar, , $ben] = rpSeminar(SeminarStatus::Held);
    $order = PaymentOrder::query()->create(['seminar_id' => $seminar->id, 'user_id' => $ben->id, 'amount_cents' => 12000, 'status' => 'open']);

    $this->actingAs(rpUser(RoleKey::Coordination))->get(route('seminars.payments.index'))->assertForbidden();

    $this->actingAs(rpUser(RoleKey::Treasury));
    $this->get(route('seminars.payments.index'))->assertOk()->assertSee('120,00 €');
    $csv = $this->get(route('seminars.payments.export'))->assertOk()->streamedContent();
    expect($csv)->toContain('120,00')->toContain($seminar->number);

    $this->post(route('seminars.payments.decide', $order), ['status' => 'rejected', 'note' => ''])->assertSessionHasErrors('payment');
    $this->post(route('seminars.payments.decide', $order), ['status' => 'rejected', 'note' => 'Betrag stimmt nicht'])->assertSessionHas('success');
    expect($order->fresh()->status)->toBe('rejected')->and($order->fresh()->note)->toBe('Betrag stimmt nicht');

    $this->post(route('seminars.payments.decide', $order), ['status' => 'paid'])->assertSessionHas('success');
    expect($order->fresh()->status)->toBe('paid')->and(AuditEntry::query()->where('event', 'seminars.payment_decided')->count())->toBe(2);

    $this->post(route('seminars.payments.decide', $order), ['status' => 'rejected', 'note' => 'doch nicht'])->assertSessionHasErrors('payment');
    expect($this->get(route('seminars.payments.export'))->streamedContent())->not->toContain('120,00');
});

it('reminds the lead before the deadline and reports overdue reports once', function () {
    [$soon, $anna] = rpSeminar(SeminarStatus::Held);
    $soon->forceFill(['report_due_on' => now()->addDays(2)])->save();
    [$late, $lateLead] = rpSeminar(SeminarStatus::Held);
    $late->forceFill(['report_due_on' => now()->subDays(2)])->save();
    $coordination = rpUser(RoleKey::Coordination);

    $this->artisan('seminars:remind-reports', ['--dry-run' => true])->expectsOutputToContain('Erinnerungen: 1, Verzug: 1')->assertSuccessful();
    Mail::assertNothingSent();

    $this->artisan('seminars:remind-reports')->expectsOutputToContain('Erinnerungen: 1, Verzug: 1')->assertSuccessful();

    Mail::assertSent(SeminarNoticeMail::class, fn (SeminarNoticeMail $mail): bool => $mail->hasTo($anna->email) && str_starts_with($mail->subjectLine, 'Bericht und Verträge fällig'));
    Mail::assertSent(SeminarNoticeMail::class, fn (SeminarNoticeMail $mail): bool => $mail->hasTo($lateLead->email) && str_starts_with($mail->subjectLine, 'Bericht und Verträge überfällig'));
    Mail::assertSent(SeminarNoticeMail::class, fn (SeminarNoticeMail $mail): bool => $mail->hasTo($coordination->email) && str_starts_with($mail->subjectLine, 'Bericht überfällig'));

    Mail::fake();
    $this->artisan('seminars:remind-reports')->expectsOutputToContain('Erinnerungen: 0, Verzug: 0')->assertSuccessful();
    Mail::assertNothingSent();
});

it('gives the school the report only after it was sent', function () {
    $partner = Partner::factory()->create();
    $contact = PartnerContact::query()->create(['partner_id' => $partner->id, 'name' => 'Frau Lehrkraft', 'email' => 'schule@schule.example']);
    $account = app(SchoolAccounts::class)->invite($partner, $contact, rpUser(RoleKey::Coordination));
    $account->user->forceFill(['status' => UserStatus::Active])->save();
    [$seminar] = rpSeminar(SeminarStatus::Reported);
    $seminar->forceFill(['partner_id' => $partner->id])->save();
    $this->actingAs(rpUser(RoleKey::Coordination));
    $this->post(route('seminars.plan.send-results', $seminar), ['results_url' => 'https://cloud.example/s/abc', 'results_expires_on' => now()->addYears(2)->toDateString()]);
    $document = $seminar->documents()->create(['kind' => 'report', 'title' => 'Bericht', 'path' => 'seminars/'.$seminar->id.'/b.pdf', 'original_name' => 'bericht.pdf', 'mime' => 'application/pdf', 'size' => 10]);
    $contract = $seminar->documents()->create(['kind' => 'team_contract', 'title' => 'Vertrag', 'path' => 'seminars/'.$seminar->id.'/v.pdf', 'original_name' => 'vertrag.pdf', 'mime' => 'application/pdf', 'size' => 10]);
    Storage::disk('local')->put($document->path, '%PDF');

    $this->actingAs($account->user->fresh())->get(route('seminars.documents.download', $document))->assertOk();
    $this->get(route('seminars.documents.download', $contract))->assertNotFound();
    $this->get(route('school.seminars.show', $seminar))->assertOk()->assertSee('Bericht und Ergebnisse')->assertSee('Ordner öffnen');
});
