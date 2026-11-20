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
use App\Modules\Quotes\Mail\SeminarQuoteMail;
use App\Modules\Quotes\Models\QuotePriceItem;
use App\Modules\Quotes\Models\QuoteText;
use App\Modules\Quotes\Models\SeminarQuote;
use App\Modules\Quotes\Services\Quotes;
use App\Modules\Quotes\Support\QuoteCalculator;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

function qtUser(?RoleKey $role = null): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

/** Seminar in Prüfung mit Schulkonto. */
function qtSeminar(SeminarStatus $status = SeminarStatus::InReview, string $email = 'eins@schule.example'): array
{
    $partner = Partner::factory()->create(['name' => 'Schule Eins']);
    $contact = PartnerContact::query()->create(['partner_id' => $partner->id, 'name' => 'Frau Lehrkraft', 'email' => $email]);
    $account = app(SchoolAccounts::class)->invite($partner, $contact, qtUser(RoleKey::Coordination));
    $account->user->forceFill(['status' => UserStatus::Active])->save();
    $seminar = Seminar::factory()->status($status)->create(['partner_id' => $partner->id, 'contact_email' => $email]);

    return [$seminar, $account->user->fresh()];
}

function qtLines(): array
{
    return [['description' => 'Projekttag Demokratie', 'quantity' => '2', 'unit' => 'Tag', 'price' => '450,00'], ['description' => 'Material', 'quantity' => '1,5', 'unit' => '', 'price' => '20']];
}

// ---------- Rechnen ----------

it('calculates lines, net, vat and total in cents', function () {
    expect(QuoteCalculator::lineTotal('1.5', 2000))->toBe(3000)
        ->and(QuoteCalculator::totals([['quantity' => 2, 'unit_price_cents' => 45000], ['quantity' => '1.5', 'unit_price_cents' => 2000]], 19))->toBe(['net' => 93000, 'vat' => 17670, 'total' => 110670])
        ->and(QuoteCalculator::totals([], 0))->toBe(['net' => 0, 'vat' => 0, 'total' => 0])
        ->and(QuoteCalculator::parseQuantity('1,5'))->toBe(1.5)->and(QuoteCalculator::parseQuantity('0'))->toBeNull()->and(QuoteCalculator::parseQuantity('abc'))->toBeNull();
});

// ---------- Anlegen und Bearbeiten ----------

it('creates a draft with the default texts, saves lines and recalculates', function () {
    [$seminar] = qtSeminar();
    $staff = qtUser(RoleKey::AdministrationStaff);

    $this->post(route('quotes.office.store', $seminar))->assertRedirect(route('login'));
    $this->actingAs(qtUser(RoleKey::Coordination))->post(route('quotes.office.store', $seminar))->assertForbidden();

    $this->actingAs($staff)->post(route('quotes.office.store', $seminar))->assertRedirect()->assertSessionHas('success');
    $quote = SeminarQuote::query()->firstOrFail();

    expect($quote->version)->toBe(1)->and($quote->status)->toBe(QuoteStatus::Draft)->and($quote->intro)->toBe(QuoteText::body('intro'))->and($quote->intro)->not->toBe('');

    $this->get(route('quotes.office.edit', [$seminar, $quote]))->assertOk()->assertSee('Kostenvoranschlag, Version 1');
    $this->put(route('quotes.office.update', [$seminar, $quote]), ['vat_percent' => '0', 'intro' => 'Guten Tag', 'outro' => 'Mit freundlichen Grüßen', 'lines' => qtLines()])->assertRedirect()->assertSessionHas('success');

    $quote->refresh();

    expect($quote->lines)->toHaveCount(2)->and($quote->net_cents)->toBe(93000)->and($quote->total_cents)->toBe(93000)->and($quote->lines[1]->quantity)->toBe('1.50');
});

it('validates the lines of a quote', function () {
    [$seminar] = qtSeminar();
    $quote = app(Quotes::class)->createDraft($seminar, qtUser(RoleKey::AdministrationStaff));
    $this->actingAs(qtUser(RoleKey::AdministrationStaff));
    $url = route('quotes.office.update', [$seminar, $quote]);

    $this->put($url, ['lines' => [['description' => '', 'quantity' => '1', 'price' => '10']]])->assertSessionHasErrors('lines.0.description');
    $this->put($url, ['lines' => [['description' => 'X', 'quantity' => 'viele', 'price' => '10']]])->assertSessionHasErrors('lines.0.quantity');
    $this->put($url, ['lines' => [['description' => 'X', 'quantity' => '1', 'price' => 'teuer']]])->assertSessionHasErrors('lines.0.price');
    $this->put($url, ['vat_percent' => 'viel', 'lines' => []])->assertSessionHasErrors('vat_percent');

    expect($quote->fresh()->lines()->count())->toBe(0);
});

it('takes a position from the price list', function () {
    [$seminar] = qtSeminar();
    $quote = app(Quotes::class)->createDraft($seminar, qtUser(RoleKey::AdministrationStaff));
    $item = QuotePriceItem::query()->create(['name' => 'Workshop', 'unit' => 'Stunde', 'unit_price_cents' => 8000, 'active' => true, 'sort_order' => 1]);

    $this->actingAs(qtUser(RoleKey::AdministrationStaff))->post(route('quotes.office.add-item', [$seminar, $quote]), ['item' => $item->id])->assertSessionHas('success');
    $this->post(route('quotes.office.add-item', [$seminar, $quote]), ['item' => 9999])->assertSessionHasErrors('item');

    $quote->refresh();

    expect($quote->lines)->toHaveCount(1)->and($quote->lines[0]->description)->toBe('Workshop')->and($quote->total_cents)->toBe(8000);
});

it('allows only one open draft and only during the review', function () {
    [$seminar] = qtSeminar();
    $staff = qtUser(RoleKey::AdministrationStaff);
    $quotes = app(Quotes::class);

    $quotes->createDraft($seminar, $staff);

    expect(fn () => $quotes->createDraft($seminar, $staff))->toThrow(DomainException::class, 'schon einen Entwurf');

    [$other] = qtSeminar(SeminarStatus::Request, 'zwei@schule.example');

    expect(fn () => $quotes->createDraft($other, $staff))->toThrow(DomainException::class, 'geprüft');
});

// ---------- Freigabe und Senden ----------

it('needs the internal approval before sending and keeps the approval only for the saved state', function () {
    Mail::fake();
    [$seminar] = qtSeminar();
    $staff = qtUser(RoleKey::AdministrationStaff);
    $quotes = app(Quotes::class);
    $quote = $quotes->createDraft($seminar, $staff);

    expect(fn () => $quotes->send($quote, $staff))->toThrow(DomainException::class, 'intern freigegeben');
    expect(fn () => $quotes->approve($quote, qtUser(RoleKey::Coordination)))->toThrow(DomainException::class, 'mindestens eine Position');

    $this->actingAs($staff)->put(route('quotes.office.update', [$seminar, $quote]), ['vat_percent' => '0', 'lines' => qtLines()]);

    // Büro darf nicht freigeben, Koordination und Vorstand schon
    $this->post(route('quotes.approval.approve', $quote))->assertForbidden();
    $this->actingAs(qtUser(RoleKey::Coordination))->get(route('quotes.approval.index'))->assertOk()->assertSee('Schule Eins');
    $this->post(route('quotes.approval.approve', $quote))->assertSessionHas('success');

    expect($quote->fresh()->status)->toBe(QuoteStatus::Approved)->and($quote->fresh()->approved_by_user_id)->not->toBeNull();

    // eine Änderung nimmt die Freigabe zurück
    $this->actingAs($staff)->put(route('quotes.office.update', [$seminar, $quote]), ['vat_percent' => '0', 'lines' => array_slice(qtLines(), 0, 1)]);

    expect($quote->fresh()->status)->toBe(QuoteStatus::Draft)->and($quote->fresh()->approved_at)->toBeNull();
});

it('sends the approved quote to the school with a pdf and moves the seminar on', function () {
    Mail::fake();
    [$seminar] = qtSeminar();
    $staff = qtUser(RoleKey::AdministrationStaff);
    $quotes = app(Quotes::class);
    $quote = $quotes->createDraft($seminar, $staff);
    $quotes->save($quote, [['description' => 'Projekttag', 'quantity' => 1.0, 'unit' => 'Tag', 'unit_price_cents' => 90000]], 0.0, 'Hallo', 'Tschüss');
    $quotes->approve($quote->fresh(), qtUser(RoleKey::BoardMember));

    $this->actingAs($staff)->post(route('quotes.office.send', [$seminar, $quote]))->assertRedirect(route('seminars.office.show', $seminar))->assertSessionHas('success');

    expect($seminar->fresh()->status)->toBe(SeminarStatus::QuoteSent)->and($quote->fresh()->status)->toBe(QuoteStatus::Sent)->and($quote->fresh()->sent_at)->not->toBeNull();

    Mail::assertSent(SeminarQuoteMail::class, fn (SeminarQuoteMail $mail): bool => $mail->hasTo('eins@schule.example') && count($mail->attachments()) === 1 && str_contains($mail->render(), $seminar->number));
    expect(AuditEntry::query()->where('event', 'seminars.quote_sent')->count())->toBe(1)->and($seminar->events()->where('type', 'mail')->count())->toBe(1);

    // gesendet heißt: nicht mehr bearbeiten
    expect(fn () => $quotes->save($quote->fresh(), [], 0.0, null, null))->toThrow(DomainException::class, 'gesendet');
});

it('renders a pdf for the office and refuses it to others', function () {
    [$seminar, $school] = qtSeminar();
    $quote = app(Quotes::class)->createDraft($seminar, qtUser(RoleKey::AdministrationStaff));

    $response = $this->actingAs(qtUser(RoleKey::AdministrationStaff))->get(route('quotes.pdf', $quote))->assertOk();
    expect($response->headers->get('Content-Type'))->toBe('application/pdf')->and(substr($response->getContent(), 0, 4))->toBe('%PDF');

    // die Schule sieht nur gesendete Versionen
    $this->actingAs($school)->get(route('quotes.pdf', $quote))->assertNotFound();
    $this->actingAs(qtUser(RoleKey::Member))->get(route('quotes.pdf', $quote))->assertNotFound();
    $this->actingAs(qtUser(RoleKey::Coordination))->get(route('quotes.pdf', $quote))->assertOk();
});

// ---------- Antwort der Schule ----------

function qtSentQuote(string $email = 'eins@schule.example'): array
{
    Mail::fake();
    [$seminar, $school] = qtSeminar(SeminarStatus::InReview, $email);
    $staff = qtUser(RoleKey::AdministrationStaff);
    $quotes = app(Quotes::class);
    $quote = $quotes->createDraft($seminar, $staff);
    $quotes->save($quote, [['description' => 'Projekttag', 'quantity' => 1.0, 'unit' => null, 'unit_price_cents' => 50000]], 0.0, null, null);
    $quotes->approve($quote->fresh(), qtUser(RoleKey::BoardMember));
    $quotes->send($quote->fresh(), $staff);

    return [$seminar->fresh(), $quote->fresh(), $school, $staff];
}

it('lets the school accept, ask and decline, but not the quote of another school', function () {
    [$seminar, $quote, $school] = qtSentQuote();

    $this->actingAs($school)->get(route('school.seminars.show', $seminar))->assertOk()->assertSee('Kostenvoranschlag annehmen')->assertSee('PDF öffnen');
    $this->actingAs($school)->get(route('quotes.pdf', $quote))->assertOk();

    $this->post(route('school.quotes.question', [$seminar, $quote]), ['note' => ''])->assertSessionHasErrors('note');
    $this->post(route('school.quotes.question', [$seminar, $quote]), ['note' => 'Geht es auch günstiger?'])->assertSessionHas('success');
    expect($seminar->events()->where('type', 'quote_question')->first()->comment)->toBe('Geht es auch günstiger?')->and($seminar->fresh()->status)->toBe(SeminarStatus::QuoteSent);

    $this->post(route('school.quotes.accept', [$seminar, $quote]))->assertSessionHas('success');
    expect($quote->fresh()->status)->toBe(QuoteStatus::Accepted)->and($quote->fresh()->decided_at)->not->toBeNull();

    // nach der Antwort lässt sich nicht noch einmal antworten
    $this->post(route('school.quotes.decline', [$seminar, $quote]))->assertSessionHasErrors('quote');

    [, $foreignQuote, $foreign] = qtSentQuote('zwei@schule.example');
    $this->actingAs($foreign)->post(route('school.quotes.accept', [$seminar, $quote]))->assertNotFound();
});

it('rejects the seminar when the school declines the quote', function () {
    [$seminar, $quote, $school] = qtSentQuote();

    $this->actingAs($school)->post(route('school.quotes.decline', [$seminar, $quote]), ['note' => 'Zu teuer'])->assertSessionHas('success');

    expect($quote->fresh()->status)->toBe(QuoteStatus::Declined)->and($seminar->fresh()->status)->toBe(SeminarStatus::Rejected)
        ->and($seminar->events()->first()->comment)->toContain('Zu teuer');
});

it('creates a new version after a question, back in the review, and supersedes the old one', function () {
    [$seminar, $quote, , $staff] = qtSentQuote();
    $quotes = app(Quotes::class);

    $new = $quotes->createDraft($seminar, $staff);

    expect($new->version)->toBe(2)->and($new->lines)->toHaveCount(1)->and($new->total_cents)->toBe(50000)->and($seminar->fresh()->status)->toBe(SeminarStatus::InReview)
        ->and($quote->fresh()->status)->toBe(QuoteStatus::Superseded);
});

// ---------- Preisliste ----------

it('saves the price list and the text blocks and validates the entries', function () {
    $this->actingAs(qtUser(RoleKey::Coordination))->get(route('quotes.office.prices'))->assertForbidden();

    $this->actingAs(qtUser(RoleKey::AdministrationStaff));
    $this->get(route('quotes.office.prices'))->assertOk()->assertSee('Preisliste');

    $this->put(route('quotes.office.prices.update'), ['items' => [['name' => 'Workshop', 'unit' => 'Stunde', 'price' => 'teuer']]])->assertSessionHasErrors('items.0.price');
    $this->put(route('quotes.office.prices.update'), ['items' => [['name' => '', 'price' => '10']]])->assertSessionHasErrors('items.0.name');

    $this->put(route('quotes.office.prices.update'), ['items' => [['name' => 'Projekttag', 'unit' => 'Tag', 'price' => '450,00', 'active' => '1'], ['name' => '', 'price' => ''], ['name' => 'Alt', 'unit' => '', 'price' => '10', 'active' => '0']], 'texts' => ['intro' => 'Neue Einleitung', 'outro' => 'Neuer Schluss']])->assertSessionHas('success');

    expect(QuotePriceItem::query()->count())->toBe(2)->and(QuotePriceItem::query()->ordered()->first()->unit_price_cents)->toBe(45000)->and(QuotePriceItem::query()->where('active', false)->count())->toBe(1)
        ->and(QuoteText::body('intro'))->toBe('Neue Einleitung')->and(AuditEntry::query()->where('event', 'seminars.quote_prices_saved')->count())->toBe(1);
});
