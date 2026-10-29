<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\Partners\Models\Partner;
use App\Modules\People\Models\Person;
use App\Modules\Seminars\Enums\SeminarEventType;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Enums\SeminarType;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarPriceList;
use App\Modules\Seminars\Models\SeminarQuote;
use App\Modules\Seminars\Services\SeminarAdministration;
use App\Modules\Seminars\Support\QuoteTexts;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

function smUser(?RoleKey $role = null): User
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

function smForm(Partner $partner, array $overrides = []): array
{
    return $overrides + ['title' => 'SV-Tage Herbst', 'type' => 'sv_days', 'partner_id' => $partner->id, 'grade_level' => 'Klasse 7 bis 10', 'expected_participants' => '45', 'notes' => 'Erstes Gespräch im Mai'];
}

function smSession(Seminar $seminar): void
{
    app(SeminarAdministration::class)->addSession($seminar, ['date' => now()->addMonth()->toDateString(), 'start' => '09:00', 'end' => '15:00'], smUser(RoleKey::Coordination));
}

function smPosition(array $overrides = []): array
{
    return $overrides + ['product' => 'sv_days', 'days' => '3', 'team' => '3', 'hours' => '6', 'leaders' => '1', 'group' => '45', 'material_factor' => '1', 'material_trips' => '0', 'boards' => '0', 'inside_abc' => '1'];
}

/** Formulardaten eines Kostenvoranschlags für eine Schule oder ein Seminar (dann mit Verknüpfung). */
function smQuote(Seminar|Partner $on, ?array $positions = null, array $extra = []): array
{
    return $extra + [
        'partner_id' => $on instanceof Seminar ? $on->partner_id : $on->id,
        'seminar_id' => $on instanceof Seminar ? $on->id : null,
        'positions' => $positions ?? [smPosition()],
        'action' => 'save',
    ];
}

// ---------- Zugang und Navigation ----------

it('sends guests to the login and forbids administration and members', function () {
    $seminar = Seminar::factory()->create();

    $this->get(route('seminars.index'))->assertRedirect(route('login'));
    $this->get(route('seminar-prices.index'))->assertRedirect(route('login'));

    foreach ([RoleKey::Administration, RoleKey::Member, RoleKey::Team] as $role) {
        $this->actingAs(smUser($role))->get(route('seminars.index'))->assertForbidden();
        $this->actingAs(smUser($role))->get(route('seminars.show', $seminar))->assertForbidden();
    }
});

it('lets coordination, staff and board work with seminars', function () {
    foreach ([RoleKey::Coordination, RoleKey::AdministrationStaff, RoleKey::BoardMember] as $role) {
        $this->actingAs(smUser($role))->get(route('seminars.index'))->assertOk();
        $this->actingAs(smUser($role))->get(route('seminars.create'))->assertOk();
    }
});

it('shows the seminar pages in the area Koordination', function () {
    $this->actingAs(smUser(RoleKey::Coordination))->get(route('seminars.index'))
        ->assertOk()->assertSee('Seminare')->assertSee('Preise')->assertSee('Seminar anlegen');
});

// ---------- Anlegen und Bearbeiten ----------

it('creates a seminar for a school with the creator and a history entry', function () {
    $coordinator = smUser(RoleKey::Coordination);
    $partner = Partner::factory()->create(['name' => 'Anne-Frank-Schule']);

    $this->actingAs($coordinator)->post(route('seminars.store'), smForm($partner))->assertSessionHasNoErrors();

    $seminar = Seminar::query()->firstOrFail();
    expect($seminar->title)->toBe('SV-Tage Herbst')
        ->and($seminar->status)->toBe(SeminarStatus::Request)
        ->and($seminar->partner_id)->toBe($partner->id)
        ->and($seminar->expected_participants)->toBe(45)
        ->and($seminar->created_by_user_id)->toBe($coordinator->id)
        ->and($seminar->events()->first()->type)->toBe(SeminarEventType::Created);

    $entry = AuditEntry::query()->where('event', 'seminar.created')->firstOrFail();
    expect($entry->subject_type)->toBe('seminar')->and($entry->subject_id)->toBe($seminar->id);

    $this->get(route('seminars.show', $seminar))->assertOk()->assertSee('SV-Tage Herbst')->assertSee('Anne-Frank-Schule')->assertSee('Anfrage');
});

it('preselects the school from the school page link', function () {
    $partner = Partner::factory()->create(['name' => 'Vorauswahlschule']);

    $this->actingAs(smUser(RoleKey::Coordination))->get(route('seminars.create', ['schule' => $partner->id]))
        ->assertOk()->assertSee('<option value="'.$partner->id.'" selected>', false);

    $this->get(route('partners.show', $partner))->assertSee(route('seminars.create', ['schule' => $partner->id]), false);
});

it('validates the seminar form', function () {
    $this->actingAs(smUser(RoleKey::Coordination));
    $partner = Partner::factory()->create();

    $this->post(route('seminars.store'), [])->assertSessionHasErrors(['title', 'type', 'partner_id']);
    $this->post(route('seminars.store'), smForm($partner, ['type' => 'weg']))->assertSessionHasErrors('type');
    $this->post(route('seminars.store'), smForm($partner, ['partner_id' => 9999]))->assertSessionHasErrors('partner_id');
    $this->post(route('seminars.store'), smForm($partner, ['expected_participants' => 'viele']))->assertSessionHasErrors('expected_participants');
    $this->post(route('seminars.store'), smForm($partner, ['expected_participants' => '0']))->assertSessionHasErrors('expected_participants');

    expect(Seminar::query()->count())->toBe(0);
});

it('edits a seminar and records only the changed field names', function () {
    $coordinator = smUser(RoleKey::Coordination);
    $partner = Partner::factory()->create();
    $seminar = Seminar::factory()->create(['partner_id' => $partner->id, 'title' => 'Alt', 'type' => 'sv_days']);

    $this->actingAs($coordinator)->get(route('seminars.edit', $seminar))->assertOk()->assertSee('Alt');
    $this->put(route('seminars.update', $seminar), smForm($partner, ['title' => 'Neu', 'type' => 'coaching']))->assertSessionHasNoErrors();

    $fresh = $seminar->fresh();
    expect($fresh->title)->toBe('Neu')->and($fresh->type->value)->toBe('coaching');

    $entry = AuditEntry::query()->where('event', 'seminar.updated')->firstOrFail();
    expect($entry->context['felder'])->toContain('title')->toContain('type')->not->toContain('notes_text');

    $count = AuditEntry::query()->where('event', 'seminar.updated')->count();
    $this->put(route('seminars.update', $seminar), smForm($partner, ['title' => 'Neu', 'type' => 'coaching']));
    expect(AuditEntry::query()->where('event', 'seminar.updated')->count())->toBe($count);
});

it('deletes only requests and cancelled seminars', function () {
    $this->actingAs(smUser(RoleKey::Coordination));
    $request = Seminar::factory()->create();
    $planning = Seminar::factory()->create(['status' => SeminarStatus::Planning]);

    $this->delete(route('seminars.destroy', $planning))->assertRedirect(route('seminars.show', $planning))->assertSessionHas('warning');
    expect(Seminar::query()->whereKey($planning->id)->exists())->toBeTrue();

    $this->delete(route('seminars.destroy', $request))->assertRedirect(route('seminars.index'));
    expect(Seminar::query()->whereKey($request->id)->exists())->toBeFalse()
        ->and(AuditEntry::query()->where('event', 'seminar.deleted')->count())->toBe(1);
});

it('does not delete a school that still has seminars', function () {
    $partner = Partner::factory()->create(['name' => 'Schule mit Seminar']);
    Seminar::factory()->create(['partner_id' => $partner->id]);

    $this->actingAs(smUser(RoleKey::Coordination))->delete(route('partners.destroy', $partner))->assertRedirect(route('partners.show', $partner))->assertSessionHas('warning');

    expect(Partner::query()->whereKey($partner->id)->exists())->toBeTrue();
});

// ---------- Liste ----------

it('lists seminars with stats, search and filters', function () {
    $coordinator = smUser(RoleKey::Coordination);
    $a = Partner::factory()->create(['name' => 'Berliner Schule', 'city' => 'Berlin']);
    $b = Partner::factory()->create(['name' => 'Leipziger Schule', 'city' => 'Leipzig']);
    Seminar::factory()->create(['title' => 'Tage Eins', 'partner_id' => $a->id, 'type' => 'sv_days', 'status' => SeminarStatus::Planning]);
    Seminar::factory()->create(['title' => 'Coaching Zwei', 'partner_id' => $b->id, 'type' => 'coaching', 'status' => SeminarStatus::Confirmed]);

    $this->actingAs($coordinator);
    $this->get(route('seminars.index'))->assertOk()->assertSee('Tage Eins')->assertSee('Coaching Zwei');
    $this->get(route('seminars.index', ['q' => 'Leipzig']))->assertSee('Coaching Zwei')->assertDontSee('Tage Eins');
    $this->get(route('seminars.index', ['status' => 'planning']))->assertSee('Tage Eins')->assertDontSee('Coaching Zwei');
    $this->get(route('seminars.index', ['type' => 'coaching']))->assertSee('Coaching Zwei')->assertDontSee('Tage Eins');
    $this->get(route('seminars.index'))->assertSee('<a class="vdbs-table__row-link" href="'.route('seminars.show', Seminar::query()->where('title', 'Tage Eins')->first()).'">', false);
});

// ---------- Termine ----------

it('adds and removes sessions with the history', function () {
    $coordinator = smUser(RoleKey::Coordination);
    $seminar = Seminar::factory()->create();
    $date = now()->addMonth()->toDateString();

    $this->actingAs($coordinator)->post(route('seminars.sessions.store', $seminar), ['date' => $date, 'start' => '09:00', 'end' => '15:30', 'location' => 'Aula'])->assertSessionHasNoErrors();

    $session = $seminar->sessions()->firstOrFail();
    expect($session->starts_at->format('H:i'))->toBe('09:00')->and($session->ends_at->format('H:i'))->toBe('15:30')->and($session->location)->toBe('Aula');

    $this->get(route('seminars.show', $seminar))->assertSee('15:30')->assertSee('Aula')->assertSee('Termin hinzugefügt');

    $this->delete(route('seminars.sessions.destroy', [$seminar, $session]))->assertRedirect(route('seminars.show', $seminar));
    expect($seminar->sessions()->count())->toBe(0)
        ->and($seminar->events()->where('type', 'session_removed')->exists())->toBeTrue();
});

it('validates the session and keeps sessions of other seminars apart', function () {
    $this->actingAs(smUser(RoleKey::Coordination));
    $seminar = Seminar::factory()->create();
    $other = Seminar::factory()->create();
    $date = now()->addMonth()->toDateString();

    $this->post(route('seminars.sessions.store', $seminar), [])->assertSessionHasErrors(['date', 'start', 'end']);
    $this->post(route('seminars.sessions.store', $seminar), ['date' => $date, 'start' => '15:00', 'end' => '09:00'])->assertSessionHasErrors('end');
    $this->post(route('seminars.sessions.store', $seminar), ['date' => '2000-01-01', 'start' => '09:00', 'end' => '10:00'])->assertSessionHasErrors('date');

    smSession($other);
    $this->delete(route('seminars.sessions.destroy', [$seminar, $other->sessions()->first()]))->assertNotFound();
});

// ---------- Status ----------

it('walks through the status pipeline with history entries', function () {
    $coordinator = smUser(RoleKey::Coordination);
    $seminar = Seminar::factory()->create();

    $this->actingAs($coordinator);
    $this->post(route('seminars.status', $seminar), ['status' => 'planning', 'comment' => 'Schule hat zugesagt'])->assertSessionHasNoErrors();
    expect($seminar->fresh()->status)->toBe(SeminarStatus::Planning);

    smSession($seminar);
    $this->post(route('seminars.status', $seminar), ['status' => 'scheduled']);
    expect($seminar->fresh()->status)->toBe(SeminarStatus::Scheduled);

    $this->post(route('seminar-quotes.store'), smQuote($seminar));
    $this->post(route('seminars.status', $seminar), ['status' => 'confirmed']);
    $this->post(route('seminars.status', $seminar), ['status' => 'done']);
    $this->post(route('seminars.status', $seminar), ['status' => 'completed']);

    expect($seminar->fresh()->status)->toBe(SeminarStatus::Completed)
        ->and($seminar->events()->where('type', 'status_changed')->count())->toBe(5);

    $entry = AuditEntry::query()->where('event', 'seminar.status_changed')->orderBy('id')->firstOrFail();
    expect($entry->context)->toEqual(['von' => 'Anfrage', 'nach' => 'In Planung']);

    $this->get(route('seminars.show', $seminar))->assertSee('Schule hat zugesagt')->assertSee('Abgeschlossen');
});

it('refuses jumps, missing sessions and a missing quote', function () {
    $this->actingAs(smUser(RoleKey::Coordination));
    $seminar = Seminar::factory()->create();

    $this->post(route('seminars.status', $seminar), ['status' => 'done'])->assertSessionHas('warning');
    expect($seminar->fresh()->status)->toBe(SeminarStatus::Request);

    $seminar->forceFill(['status' => SeminarStatus::Planning])->save();
    $this->post(route('seminars.status', $seminar), ['status' => 'scheduled'])->assertSessionHas('warning');
    expect($seminar->fresh()->status)->toBe(SeminarStatus::Planning);

    smSession($seminar);
    $seminar->forceFill(['status' => SeminarStatus::Scheduled])->save();
    $this->post(route('seminars.status', $seminar), ['status' => 'confirmed'])->assertSessionHas('warning');
    expect($seminar->fresh()->status)->toBe(SeminarStatus::Scheduled);

    $this->post(route('seminars.status', $seminar), ['status' => 'weg'])->assertSessionHasErrors('status');
});

it('cancels a seminar at any time and reopens it as a request', function () {
    $this->actingAs(smUser(RoleKey::Coordination));
    $seminar = Seminar::factory()->create(['status' => SeminarStatus::Confirmed]);

    $this->post(route('seminars.status', $seminar), ['status' => 'cancelled', 'comment' => 'Schule sagt ab']);
    expect($seminar->fresh()->status)->toBe(SeminarStatus::Cancelled);

    $this->post(route('seminars.status', $seminar), ['status' => 'request']);
    expect($seminar->fresh()->status)->toBe(SeminarStatus::Request);
});

it('adds notes to the history', function () {
    $this->actingAs(smUser(RoleKey::Coordination));
    $seminar = Seminar::factory()->create();

    $this->post(route('seminars.note', $seminar), ['note' => 'Rückruf am Montag'])->assertSessionHasNoErrors();
    $this->post(route('seminars.note', $seminar), ['note' => ''])->assertSessionHasErrors('note');

    $this->get(route('seminars.show', $seminar))->assertSee('Rückruf am Montag');
});

// ---------- Kostenvoranschlag ----------

it('prefills the quote form from the seminar with its product and expected participants', function () {
    $seminar = Seminar::factory()->create(['type' => 'coaching', 'expected_participants' => 33]);

    $html = $this->actingAs(smUser(RoleKey::Coordination))->get(route('seminar-quotes.create', ['seminar' => $seminar->id]))->assertOk()->getContent();

    expect($html)->toContain('<option value="coaching" selected>')
        ->toContain('name="positions[0][days]"')
        ->toContain('value="33"')
        ->toContain('<option value="'.$seminar->partner_id.'" selected>')
        ->toContain('Weitere Position hinzufügen')
        ->toContain('<template data-vdbs-repeater-template>')
        ->toContain('positions[__INDEX__][product]');
});

it('offers one filled row and one spare row without a product', function () {
    $partner = Partner::factory()->create();

    $html = $this->actingAs(smUser(RoleKey::Coordination))->get(route('seminar-quotes.create', ['schule' => $partner->id]))->assertOk()->getContent();

    expect(substr_count($html, 'data-vdbs-position '))->toBe(3)
        ->and(substr_count($html, '<details class="vdbs-accordion__item"'))->toBe(3)
        ->and($html)->toContain('data-vdbs-repeater-next="2"')
        ->toContain('SV-Tage – 3 Tage')
        ->toContain('Neue Position');
});

it('previews several positions without saving', function () {
    $partner = Partner::factory()->create();
    $positions = [smPosition(), smPosition(['product' => 'coaching', 'days' => '2', 'team' => '3', 'leaders' => '0', 'group' => '30', 'material_factor' => '0.3'])];

    $this->actingAs(smUser(RoleKey::Coordination))->post(route('seminar-quotes.store'), smQuote($partner, $positions, ['action' => 'preview']))
        ->assertOk()->assertSee('Position 1')->assertSee('Position 2')->assertSee('2.309,00 €')->assertSee('1.392,20 €')->assertSee('3.701,20 €')->assertSee('Noch nicht gespeichert');

    expect(SeminarQuote::query()->count())->toBe(0);
});

it('saves an immutable quote with several positions, each with its own club fee', function () {
    $coordinator = smUser(RoleKey::Coordination);
    $seminar = Seminar::factory()->create();
    $positions = [smPosition(), smPosition(['product' => 'election', 'days' => '1', 'team' => '2', 'hours' => '2', 'leaders' => '0', 'group' => '20', 'material_factor' => '0.3'])];

    $this->actingAs($coordinator)->post(route('seminar-quotes.store'), smQuote($seminar, $positions))->assertSessionHasNoErrors();

    $quote = SeminarQuote::query()->firstOrFail();
    expect($quote->partner_id)->toBe($seminar->partner_id)
        ->and($quote->seminar_id)->toBe($seminar->id)
        ->and($quote->total_cents)->toBe(230900 + 31460)
        ->and($quote->price_list_id)->toBe(SeminarPriceList::current()->id)
        ->and($quote->positions)->toHaveCount(2)
        ->and($quote->positions[0]['title'])->toBe('SV-Tage (3)')
        ->and($quote->positions[1]['title'])->toBe('SV-Wahl')
        ->and($quote->positions[1]['total_cents'])->toBe(31460)
        ->and(collect($quote->positions)->every(fn (array $position): bool => collect($position['lines'])->contains('key', 'club_fee')))->toBeTrue()
        ->and($seminar->events()->where('type', 'quote_created')->exists())->toBeTrue()
        ->and(AuditEntry::query()->where('event', 'seminar.quote_created')->exists())->toBeTrue();

    $quote->update(['total_cents' => 1]);
    expect($quote->fresh()->total_cents)->toBe(230900 + 31460);

    $this->get(route('seminar-quotes.show', $quote))->assertOk()->assertSee('Position 1: SV-Tage (3)')->assertSee('Position 2: SV-Wahl')->assertSee('2.623,60 €')->assertSee('Honorar der Teamenden')->assertSee('Unverbindlicher Kostenvoranschlag')->assertSee(route('seminar-quotes.print', $quote), false);
});

it('works without a seminar and ignores rows without a product', function () {
    $partner = Partner::factory()->create();
    $positions = [0 => smPosition(), 1 => ['product' => '', 'days' => '3'], 4 => smPosition(['product' => 'gsv_support', 'days' => '4', 'team' => '1', 'hours' => '3', 'leaders' => '0', 'group' => '20', 'material_factor' => '0.3'])];

    $this->actingAs(smUser(RoleKey::Coordination))->post(route('seminar-quotes.store'), smQuote($partner, $positions))->assertSessionHasNoErrors();

    $quote = SeminarQuote::query()->firstOrFail();
    expect($quote->seminar_id)->toBeNull()->and($quote->positions)->toHaveCount(2)->and($quote->positions[1]['title'])->toBe('Begleitung GSV-Sitzungen (4 Sitzungen)')->and($quote->total_cents)->toBe(230900 + 76340);
});

it('copies a quote as template for a new one', function () {
    $coordinator = smUser(RoleKey::Coordination);
    $partner = Partner::factory()->create();
    $this->actingAs($coordinator)->post(route('seminar-quotes.store'), smQuote($partner, [smPosition(['extra' => 'Eigene Ergänzung'])]));
    $quote = SeminarQuote::query()->firstOrFail();

    $html = $this->get(route('seminar-quotes.create', ['vorlage' => $quote->id]))->assertOk()->getContent();

    expect($html)->toContain('Eigene Ergänzung')->toContain('<option value="'.$partner->id.'" selected>');
});

it('validates schools, positions and inputs', function () {
    $this->actingAs(smUser(RoleKey::Coordination));
    $partner = Partner::factory()->create();
    $other = Seminar::factory()->create();

    $this->post(route('seminar-quotes.store'), [])->assertSessionHasErrors(['partner_id', 'positions']);
    $this->post(route('seminar-quotes.store'), smQuote($partner, []))->assertSessionHasErrors('positions');
    $this->post(route('seminar-quotes.store'), smQuote($partner, [smPosition(['days' => '0'])]))->assertSessionHasErrors('positions.0.days');
    $this->post(route('seminar-quotes.store'), smQuote($partner, [smPosition(['hours' => 'viel'])]))->assertSessionHasErrors('positions.0.hours');
    $this->post(route('seminar-quotes.store'), smQuote($partner, [smPosition(['group' => '99999'])]))->assertSessionHasErrors('positions.0.group');
    $this->post(route('seminar-quotes.store'), smQuote($partner, [smPosition(['product' => 'weg'])]))->assertSessionHasErrors('positions.0.product');
    $this->post(route('seminar-quotes.store'), smQuote($partner, null, ['partner_id' => 9999]))->assertSessionHasErrors('partner_id');
    $this->post(route('seminar-quotes.store'), smQuote($partner, null, ['seminar_id' => $other->id]))->assertSessionHasErrors('seminar_id');

    expect(SeminarQuote::query()->count())->toBe(0);
});

it('keeps old quotes on their price list after a new version of the prices', function () {
    $coordinator = smUser(RoleKey::Coordination);
    $partner = Partner::factory()->create();
    $this->actingAs($coordinator)->post(route('seminar-quotes.store'), smQuote($partner));

    $prices = collect(SeminarPriceList::current()->prices)->mapWithKeys(fn ($cents, $key) => ['price_'.$key => number_format($cents / 100, 2, ',', '')])->all();
    $prices['price_honorarium_full'] = '250,00';

    $this->post(route('seminar-prices.store'), ['label' => 'Preisstand Januar 2027', 'valid_from' => now()->toDateString()] + $prices)->assertSessionHasNoErrors();

    expect(SeminarPriceList::current()->prices['honorarium_full'])->toBe(25000)
        ->and(SeminarQuote::query()->first()->total_cents)->toBe(230900);

    $this->post(route('seminar-quotes.store'), smQuote($partner));
    expect(SeminarQuote::query()->orderByDesc('id')->first()->total_cents)->toBe(230900 + 3 * 5000 * 3);
});

it('lists quotes with school, positions and total and searches by school', function () {
    $a = Partner::factory()->create(['name' => 'Berliner Schule', 'city' => 'Berlin']);
    $b = Partner::factory()->create(['name' => 'Leipziger Schule', 'city' => 'Leipzig']);
    $this->actingAs(smUser(RoleKey::Coordination));
    $this->post(route('seminar-quotes.store'), smQuote($a));
    $this->post(route('seminar-quotes.store'), smQuote($b, [smPosition(['product' => 'coaching', 'days' => '6'])]));

    $this->get(route('seminar-quotes.index'))->assertOk()->assertSee('Berliner Schule')->assertSee('Leipziger Schule')->assertSee('SV-Coaching (6 Treffen)')->assertSee('2.309,00 €');
    $this->get(route('seminar-quotes.index', ['q' => 'Leipzig']))->assertSee('Leipziger Schule')->assertDontSee('Berliner Schule');
});

it('deletes a quote with a log entry but protects the only quote of a confirmed seminar', function () {
    $this->actingAs(smUser(RoleKey::Coordination));
    $seminar = Seminar::factory()->create(['status' => SeminarStatus::Confirmed]);
    $this->post(route('seminar-quotes.store'), smQuote($seminar));
    $quote = SeminarQuote::query()->firstOrFail();

    $this->delete(route('seminar-quotes.destroy', $quote))->assertRedirect(route('seminar-quotes.show', $quote))->assertSessionHas('warning');
    expect(SeminarQuote::query()->count())->toBe(1);

    $this->post(route('seminar-quotes.store'), smQuote($seminar));
    $this->delete(route('seminar-quotes.destroy', $quote))->assertRedirect(route('seminar-quotes.index'));

    expect(SeminarQuote::query()->count())->toBe(1)->and(AuditEntry::query()->where('event', 'seminar.quote_deleted')->exists())->toBeTrue();
});

it('lets staff view quotes and keeps guests and members out', function () {
    $partner = Partner::factory()->create();
    $this->actingAs(smUser(RoleKey::Coordination))->post(route('seminar-quotes.store'), smQuote($partner));
    $quote = SeminarQuote::query()->firstOrFail();

    $this->app['auth']->forgetGuards();
    $this->get(route('seminar-quotes.index'))->assertRedirect(route('login'));
    $this->get(route('seminar-quotes.print', $quote))->assertRedirect(route('login'));

    $this->actingAs(smUser(RoleKey::Member))->get(route('seminar-quotes.index'))->assertForbidden();
    $this->get(route('seminar-quotes.show', $quote))->assertForbidden();
    $this->get(route('seminar-quotes.print', $quote))->assertForbidden();

    $this->actingAs(smUser(RoleKey::AdministrationStaff))->get(route('seminar-quotes.show', $quote))->assertOk();
});

it('does not delete a school that has a quote', function () {
    $partner = Partner::factory()->create(['name' => 'Schule mit KVA']);
    $this->actingAs(smUser(RoleKey::Coordination))->post(route('seminar-quotes.store'), smQuote($partner));

    $this->delete(route('partners.destroy', $partner))->assertRedirect(route('partners.show', $partner))->assertSessionHas('warning');
    expect(Partner::query()->whereKey($partner->id)->exists())->toBeTrue();
});

// ---------- Preisliste ----------

it('shows the price list to everyone who may see seminars and lets only prices managers change it', function () {
    $this->actingAs(smUser(RoleKey::AdministrationStaff))->get(route('seminar-prices.index'))->assertOk()->assertSee('Preisstand Oktober 2025')->assertSee('200,00 €')->assertDontSee('Neue Fassung anlegen');
    $this->get(route('seminar-prices.create'))->assertForbidden();
    $this->post(route('seminar-prices.store'), [])->assertForbidden();

    $this->actingAs(smUser(RoleKey::Coordination))->get(route('seminar-prices.index'))->assertSee('Neue Fassung anlegen');
    $this->get(route('seminar-prices.create'))->assertOk()->assertSee('200,00');
});

it('validates the new price list', function () {
    $this->actingAs(smUser(RoleKey::Coordination));
    $prices = collect(SeminarPriceList::current()->prices)->mapWithKeys(fn ($cents, $key) => ['price_'.$key => number_format($cents / 100, 2, ',', '')])->all();

    $this->post(route('seminar-prices.store'), [])->assertSessionHasErrors(['label', 'valid_from', 'price_orga_day']);
    $this->post(route('seminar-prices.store'), ['label' => 'X', 'valid_from' => now()->toDateString(), 'price_orga_day' => 'viel'] + $prices)->assertSessionHasErrors('price_orga_day');

    expect(SeminarPriceList::query()->count())->toBe(1);

    $this->post(route('seminar-prices.store'), ['label' => 'Neu', 'valid_from' => now()->toDateString()] + $prices)->assertSessionHasNoErrors();
    expect(SeminarPriceList::query()->count())->toBe(2)
        ->and(AuditEntry::query()->where('event', 'seminar.prices_changed')->exists())->toBeTrue();
});

it('uses a price list only from its start date', function () {
    $coordinator = smUser(RoleKey::Coordination);
    $prices = collect(SeminarPriceList::current()->prices)->mapWithKeys(fn ($cents, $key) => ['price_'.$key => number_format($cents / 100, 2, ',', '')])->all();

    $this->actingAs($coordinator)->post(route('seminar-prices.store'), ['label' => 'Später', 'valid_from' => now()->addMonth()->toDateString()] + $prices)->assertSessionHasNoErrors();

    expect(SeminarPriceList::current()->label)->toBe('Preisstand Oktober 2025');
});

// ---------- Protokoll ----------

it('links the seminar from the log', function () {
    $seminar = Seminar::factory()->create(['title' => 'Protokollseminar']);
    app(SeminarAdministration::class)->update($seminar, ['title' => 'Protokollseminar 2', 'type' => 'sv_days', 'partner_id' => $seminar->partner_id], smUser(RoleKey::Coordination));

    $this->actingAs(smUser(RoleKey::BoardMember))->get(route('admin.audit.index', ['subject' => 'seminar:'.$seminar->id]))
        ->assertOk()->assertSee('Seminar geändert')->assertSee(route('seminars.show', $seminar), false);
});

// ---------- Schreiben (Druckansicht) ----------

it('prints the quote as a letter with address, all positions, total and footer', function () {
    $partner = Partner::factory()->create(['name' => 'Walter-Gropius-Schule', 'street' => 'Fritz-Erler-Allee', 'house_number' => '86-96', 'postal_code' => '12351', 'city' => 'Berlin', 'country_code' => 'DE']);
    $coordinator = smUser(RoleKey::Coordination);
    $positions = [smPosition(), smPosition(['product' => 'gsv_support', 'days' => '4', 'team' => '1', 'hours' => '3', 'leaders' => '0', 'group' => '20', 'material_factor' => '0.3'])];

    $this->actingAs($coordinator)->post(route('seminar-quotes.store'), smQuote($partner, $positions));
    $quote = SeminarQuote::query()->firstOrFail();

    $html = $this->get(route('seminar-quotes.print', $quote))->assertOk()->getContent();

    expect($html)
        ->toContain('Kostenvoranschlag')
        ->toContain('Schulleitung')
        ->toContain('Walter-Gropius-Schule')
        ->toContain('Fritz-Erler-Allee 86-96')
        ->toContain('12351 Berlin')
        ->toContain('Deutschland')
        ->toContain('SV-Tage (3)')
        ->toContain('Je 3 Teamer:innen pro Tag')
        ->toContain('Moderationsmaterial')
        ->toContain('Begleitung GSV-Sitzungen (4 Sitzungen)')
        ->toContain('Unterstützung der Planung und Organisation, sowie Unterstützung vor Ort')
        ->toContain('2.309,00 €')
        ->toContain('763,40 €')
        ->toContain('Endsumme')
        ->toContain('3.072,40 €')
        ->toContain($quote->created_at->format('d.m.Y'))
        ->toContain('bearbeitet von:')
        ->toContain(e($coordinator->person->fullName()))
        ->toContain('IBAN: DE63 1005 0000 0191 2739 96')
        ->toContain('Vereinsvorstand');

    expect(substr_count($html, '<td class="pos">'))->toBe(2);
});

it('prints the global texts with the position values and appends the extra lines', function () {
    $this->actingAs(smUser(RoleKey::Coordination));
    $partner = Partner::factory()->create();

    $this->post(route('seminar-quotes.store'), smQuote($partner, [
        smPosition(['product' => 'coaching', 'days' => '8', 'team' => '3', 'leaders' => '0', 'extra' => "Einmal pro Monat ein Treffen\n\nOnline-Nachbesprechung"]),
        smPosition(['product' => 'coaching', 'days' => '4', 'leaders' => '0']),
    ]));

    $quote = SeminarQuote::query()->firstOrFail();
    expect($quote->positions[0]['title'])->toBe('SV-Coaching (8 Treffen)')
        ->and($quote->positions[0]['content'])->toContain('Je 3 Teamer:innen')
        ->and($quote->positions[0]['extra'])->toBe("Einmal pro Monat ein Treffen\n\nOnline-Nachbesprechung")
        ->and($quote->positions[1]['title'])->toBe('SV-Coaching (4 Treffen)');

    $this->get(route('seminar-quotes.print', $quote))
        ->assertSee('SV-Coaching (8 Treffen)')->assertSee('Unterstützung von Planung, Organisation und Absprache')
        ->assertSee('Einmal pro Monat ein Treffen')->assertSee('Online-Nachbesprechung')->assertSee('SV-Coaching (4 Treffen)');
});

it('does not let the calculator change title or standard description', function () {
    $this->actingAs(smUser(RoleKey::Coordination));
    $partner = Partner::factory()->create();

    $this->post(route('seminar-quotes.store'), smQuote($partner, [smPosition(['title' => 'Selbst gewählter Titel', 'content' => 'Selbst gewählter Inhalt'])]));

    $position = SeminarQuote::query()->firstOrFail()->positions[0];
    expect($position['title'])->toBe('SV-Tage (3)')->and($position['content'])->not->toContain('Selbst gewählter Inhalt');

    $html = $this->get(route('seminar-quotes.create'))->getContent();
    expect($html)->not->toContain('[title]')->toContain('[extra]');
});

it('links the seminar quotes from the seminar page', function () {
    $seminar = Seminar::factory()->create();
    $this->actingAs(smUser(RoleKey::Coordination))->post(route('seminar-quotes.store'), smQuote($seminar));
    $quote = SeminarQuote::query()->firstOrFail();

    $this->get(route('seminars.show', $seminar))->assertSee(route('seminar-quotes.show', $quote), false)->assertSee('SV-Tage (3)')->assertSee(route('seminar-quotes.create', ['seminar' => $seminar->id]), false)->assertSee('2.309,00 €');
});

// ---------- Texte der Produkte ----------

function smTexts(array $overrides = []): array
{
    return ['texts' => $overrides + [
        'sv_days' => ['title' => 'SV-Tage ({stueckzahl})', 'content' => "Je {teamende} Teamer:innen pro Tag\nModerationsmaterial\nRaum und ggf. Verpflegung werden durch die Schule gestellt"],
        'coaching' => ['title' => 'SV-Coaching ({stueckzahl} {einheit})', 'content' => "Je {teamende} Teamer:innen\nUnterstützung von Planung, Organisation und Absprache"],
        'election' => ['title' => 'SV-Wahl', 'content' => "Je {teamende} Teamer:innen\nBegleitung und Durchführung der Wahl\nModerationsmaterial"],
        'gsv_support' => ['title' => 'Begleitung GSV-Sitzungen ({stueckzahl} {einheit})', 'content' => 'Unterstützung der Planung und Organisation, sowie Unterstützung vor Ort'],
    ]];
}

it('shows the texts to everyone who may see seminars and lets only prices managers change them', function () {
    $this->get(route('seminar-texts.index'))->assertRedirect(route('login'));
    $this->actingAs(smUser(RoleKey::Member))->get(route('seminar-texts.index'))->assertForbidden();

    $this->actingAs(smUser(RoleKey::AdministrationStaff))->get(route('seminar-texts.index'))->assertOk()
        ->assertSee('SV-Tage ({stueckzahl})', false)->assertSee('{teamende}', false)->assertDontSee('Texte speichern');
    $this->put(route('seminar-texts.update'), smTexts())->assertForbidden();

    $this->actingAs(smUser(RoleKey::Coordination))->get(route('seminar-texts.index'))->assertOk()->assertSee('Texte speichern')->assertSee('name="texts[coaching][title]"', false);
});

it('saves global texts with placeholders and logs the change', function () {
    $coordinator = smUser(RoleKey::Coordination);
    $partner = Partner::factory()->create();
    $this->actingAs($coordinator)->post(route('seminar-quotes.store'), smQuote($partner));
    $old = SeminarQuote::query()->firstOrFail();

    $this->put(route('seminar-texts.update'), smTexts(['sv_days' => ['title' => 'Schul-SV-Tage ({stueckzahl} {einheit}, {produkt})', 'content' => "Je {teamende} Teamer:innen für {gruppe} Teilnehmende\n\nBetreuung {stunden} Stunden pro Tag"]]))->assertSessionHasNoErrors();

    expect(QuoteTexts::template(SeminarType::SvDays)['title'])->toBe('Schul-SV-Tage ({stueckzahl} {einheit}, {produkt})')
        ->and(QuoteTexts::template(SeminarType::SvDays)['content'])->toBe("Je {teamende} Teamer:innen für {gruppe} Teilnehmende\nBetreuung {stunden} Stunden pro Tag");

    $entry = AuditEntry::query()->where('event', 'seminar.texts_changed')->firstOrFail();
    expect($entry->context['produkte'])->toBe(['sv_days']);

    $this->post(route('seminar-quotes.store'), smQuote($partner));
    $new = SeminarQuote::query()->orderByDesc('id')->firstOrFail();

    expect($new->positions[0]['title'])->toBe('Schul-SV-Tage (3 Tage, SV-Tage)')
        ->and($new->positions[0]['content'])->toBe("Je 3 Teamer:innen für 45 Teilnehmende\nBetreuung 6 Stunden pro Tag")
        ->and($old->fresh()->positions[0]['title'])->toBe('SV-Tage (3)');

    $this->put(route('seminar-texts.update'), smTexts(['sv_days' => ['title' => 'Schul-SV-Tage ({stueckzahl} {einheit}, {produkt})', 'content' => "Je {teamende} Teamer:innen für {gruppe} Teilnehmende\nBetreuung {stunden} Stunden pro Tag"]]));
    expect(AuditEntry::query()->where('event', 'seminar.texts_changed')->count())->toBe(1);
});

it('refuses unknown placeholders and empty texts', function () {
    $this->actingAs(smUser(RoleKey::Coordination));

    $this->put(route('seminar-texts.update'), smTexts(['sv_days' => ['title' => 'SV ({unbekannt})', 'content' => 'Text']]))->assertSessionHasErrors('texts.sv_days.title');
    $this->put(route('seminar-texts.update'), smTexts(['coaching' => ['title' => 'Coaching', 'content' => 'Je {gast} Personen']]))->assertSessionHasErrors('texts.coaching.content');
    $this->put(route('seminar-texts.update'), smTexts(['election' => ['title' => '', 'content' => '']]))->assertSessionHasErrors(['texts.election.title', 'texts.election.content']);
    $this->put(route('seminar-texts.update'), [])->assertSessionHasErrors();

    expect(QuoteTexts::template(SeminarType::SvDays)['title'])->toBe('SV-Tage ({stueckzahl})');
});

it('fills the placeholders from the position values', function () {
    $inputs = ['days' => 4, 'team' => 2, 'hours' => 4.5, 'group' => 30, 'leaders' => 1];

    expect(QuoteTexts::render('{produkt}: {stueckzahl} {einheit}, {teamende} Teamende, {stunden} h, {gruppe} TN, {leitung} SL', SeminarType::GsvSupport, $inputs))->toBe('Begleitung GSV-Sitzungen: 4 Sitzungen, 2 Teamende, 4,5 h, 30 TN, 1 SL')
        ->and(QuoteTexts::render('{stunden} h und {unbekannt}', SeminarType::SvDays, ['hours' => 6.0] + $inputs))->toBe('6 h und {unbekannt}')
        ->and(QuoteTexts::unknownPlaceholders('{teamende} {foo} {foo} {bar}'))->toBe(['foo', 'bar']);
});
