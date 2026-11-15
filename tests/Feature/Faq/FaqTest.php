<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Faq\Models\FaqCategory;
use App\Modules\Faq\Models\FaqEntry;
use App\Modules\Faq\Support\FaqText;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

function fqUser(?RoleKey $role): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

function fqCategory(string $title): FaqCategory
{
    return FaqCategory::query()->where('title', $title)->firstOrFail();
}

// ---------- Öffentliche Seite ----------

it('shows all faq categories and entries on one page with a safe formatting of the answers', function () {
    $html = $this->get(route('portal.faq'))->assertOk()->getContent();

    expect($html)->toContain('Häufig gestellte Fragen')
        ->toContain('Das Vereinsportal im Überblick')->toContain('Konto, E-Mail-Adresse und Daten')->toContain('Workshops und Seminare für Schulen')->toContain('Datenschutz und Barrierefreiheit')
        ->toContain('Was ist das Vereinsportal und wofür wurde es entwickelt?')->toContain('Wie setze ich mein Passwort zurück?')->toContain('Wie kann ich Teamer:in werden?')
        ->toContain('<li><strong>Teamer:innen:</strong>')->toContain('<a href="/passwort-vergessen">Passwort vergessen</a>')
        ->toContain('href="#kategorie-')->toContain('Uns schreiben');
});

it('does not depend on the contact area', function () {
    // die Fragen stehen in eigenen Tabellen und erscheinen auch ohne jedes Kontaktthema
    \App\Modules\ContactForms\Models\ContactTopic::query()->delete();

    $this->get(route('portal.faq'))->assertOk()->assertSee('Wie setze ich mein Passwort zurück?');
});

it('searches the questions and answers', function () {
    $html = $this->get(route('portal.faq', ['q' => 'authenticator']))->assertOk()->getContent();

    expect($html)->toContain('Ich habe das Gerät mit meiner Authenticator-App verloren.')->toContain('Was ist die Zwei-Faktor-Anmeldung?')->not->toContain('Wie kann ich Teamer:in werden?');

    $this->get(route('portal.faq', ['q' => 'gibtesnicht']))->assertOk()->assertSee('Nichts gefunden');
});

it('keeps hidden categories off the page', function () {
    fqCategory('Teamarbeit, Verträge und Abrechnung')->update(['in_faq_page' => false]);
    fqCategory('Mitgliedschaft')->update(['is_active' => false]);

    $html = $this->get(route('portal.faq'))->assertOk()->getContent();

    expect($html)->not->toContain('Wie kann ich Teamer:in werden?')->not->toContain('Wie werde ich Mitglied?')->toContain('Wie setze ich mein Passwort zurück?');
});

it('escapes html in the answers and allows only safe links', function () {
    $html = FaqText::html("<script>alert(1)</script> **fett**\n\n- Eins\n- [Intern](/kontakt)\n\n[Böse](javascript:alert(1)) [Auch böse](//boese.example) [Gut](https://beispiel.example/a?x=1&y=2)")->toHtml();

    expect($html)->not->toContain('<script>')->toContain('&lt;script&gt;')->toContain('<strong>fett</strong>')->toContain('<ul><li>Eins</li><li><a href="/kontakt">Intern</a></li></ul>')
        ->not->toContain('href="javascript:')->not->toContain('href="//boese.example"')->toContain('<a href="https://beispiel.example/a?x=1&amp;y=2">Gut</a>');
});

it('moved the questions of the former contact topics into categories', function () {
    expect(FaqCategory::query()->count())->toBeGreaterThanOrEqual(8)->and(FaqEntry::query()->count())->toBeGreaterThan(25);

    // Kontaktthemen verweisen auf die Kategorie, die Fragen liegen nur noch dort
    $topic = \App\Modules\ContactForms\Models\ContactTopic::query()->where('title', 'Passwort vergessen oder Anmeldung klappt nicht')->firstOrFail();

    expect($topic->faq_category_id)->toBe(fqCategory('Passwort vergessen oder Anmeldung klappt nicht')->id)
        ->and(fqCategory('Passwort vergessen oder Anmeldung klappt nicht')->entries)->toHaveCount(5);
});

// ---------- Verwaltung ----------

it('keeps the faq administration to administration and the office', function () {
    $category = fqCategory('Zugang beantragen');

    $this->get(route('faq-admin.index'))->assertRedirect(route('login'));
    $this->actingAs(fqUser(RoleKey::Team))->get(route('faq-admin.index'))->assertForbidden();
    $this->actingAs(fqUser(RoleKey::Team))->get(route('faq-admin.entries.index', $category))->assertForbidden();
    $this->actingAs(fqUser(RoleKey::Coordination))->get(route('faq-admin.create'))->assertForbidden();

    foreach ([RoleKey::Administration, RoleKey::AdministrationStaff] as $role) {
        $this->actingAs(fqUser($role));
        $this->get(route('faq-admin.index'))->assertOk()->assertSee('Konto, E-Mail-Adresse und Daten');
        $this->get(route('faq-admin.create'))->assertOk();
        $this->get(route('faq-admin.edit', $category))->assertOk()->assertSee('Fragen bearbeiten');
        $this->get(route('faq-admin.entries.index', $category))->assertOk()->assertSee('Wer kann Zugang zum Portal beantragen?');
        $this->get(route('faq-admin.entries.create', $category))->assertOk();
        $this->get(route('faq-admin.entries.edit', [$category, $category->entries->first()]))->assertOk()->assertSee('Frage bearbeiten');
    }

    $this->actingAs(fqUser(RoleKey::AdministrationStaff))->get(route('office.home'))->assertSee('FAQ');
});

it('shows the questions as a table with the own selection boxes of the design system', function () {
    $this->actingAs(fqUser(RoleKey::Administration));
    $category = fqCategory('Zugang beantragen');

    $html = $this->get(route('faq-admin.entries.index', $category))->assertOk()->getContent();

    expect($html)->toContain('<table')->toContain('data-vdbs-select-all="bulk-faq-entries"')->toContain('class="vdbs-table__select"')->toContain('class="vdbs-choice__input" type="checkbox" name="ids[]"')->toContain('form="bulk-faq-entries"')
        ->toContain(route('faq-admin.entries.edit', [$category, $category->entries->first()]))
        ->and(substr_count($html, 'name="ids[]"'))->toBe($category->entries->count());

    // keine rohen Kästchen mehr in den Tabellen der Verwaltung
    foreach ([route('faq-admin.index'), route('contact-admin.topics.index'), route('contact-admin.forms.index')] as $url) {
        $page = $this->get($url)->assertOk()->getContent();

        expect($page)->toContain('class="vdbs-table__select"')->not->toMatch('/<input type="checkbox" name="ids\[\]"/');
    }
});

it('creates a category and then its questions on their own pages', function () {
    $this->actingAs(fqUser(RoleKey::AdministrationStaff));

    $response = $this->post(route('faq-admin.store'), ['title' => 'Veranstaltungen', 'description' => 'Rund um Termine', 'sort_order' => 3, 'is_active' => '1', 'in_faq_page' => '1']);
    $category = fqCategory('Veranstaltungen');

    $response->assertRedirect(route('faq-admin.entries.index', $category))->assertSessionHas('success');

    $this->post(route('faq-admin.entries.store', $category), ['question' => 'Wo finde ich Termine?', 'answer' => "Im Kalender.\n\n- Eins\n- Zwei", 'sort_order' => 10, 'is_active' => '1'])->assertRedirect(route('faq-admin.entries.index', $category));

    $entry = $category->entries()->firstOrFail();

    expect($entry->question)->toBe('Wo finde ich Termine?')->and($entry->is_active)->toBeTrue();

    $this->get(route('portal.faq'))->assertSee('Wo finde ich Termine?')->assertSee('<li>Eins</li>', false);

    $this->put(route('faq-admin.entries.update', [$category, $entry]), ['question' => 'Wo gibt es Termine?', 'answer' => 'Im Kalender.', 'sort_order' => 20])->assertRedirect(route('faq-admin.entries.index', $category));

    expect($entry->fresh()->question)->toBe('Wo gibt es Termine?')->and($entry->fresh()->is_active)->toBeFalse();
    $this->get(route('portal.faq'))->assertDontSee('Wo gibt es Termine?');

    $this->put(route('faq-admin.update', $category), ['title' => 'Veranstaltungen und Termine', 'is_active' => '1', 'in_faq_page' => '1'])->assertRedirect(route('faq-admin.index'));
    expect($category->fresh()->title)->toBe('Veranstaltungen und Termine');

    $this->delete(route('faq-admin.entries.destroy', [$category, $entry]))->assertRedirect(route('faq-admin.entries.index', $category));
    expect(FaqEntry::query()->whereKey($entry->id)->exists())->toBeFalse();

    $this->delete(route('faq-admin.destroy', $category))->assertRedirect(route('faq-admin.index'));
    expect(FaqCategory::query()->whereKey($category->id)->exists())->toBeFalse();
});

it('does not touch a question through another category', function () {
    $this->actingAs(fqUser(RoleKey::Administration));
    $entry = fqCategory('Zugang beantragen')->entries->first();
    $other = fqCategory('Mitgliedschaft');

    $this->get(route('faq-admin.entries.edit', [$other, $entry]))->assertNotFound();
    $this->put(route('faq-admin.entries.update', [$other, $entry]), ['question' => 'X', 'answer' => 'Y'])->assertNotFound();
    $this->delete(route('faq-admin.entries.destroy', [$other, $entry]))->assertNotFound();

    expect($entry->fresh()->question)->not->toBe('X');
});

it('validates the faq categories and questions', function () {
    $this->actingAs(fqUser(RoleKey::Administration));
    $category = fqCategory('Zugang beantragen');

    $this->post(route('faq-admin.store'), ['title' => ''])->assertSessionHasErrors('title');
    $this->post(route('faq-admin.store'), ['title' => str_repeat('x', 151)])->assertSessionHasErrors('title');
    $this->post(route('faq-admin.entries.store', $category), ['question' => '', 'answer' => 'Antwort'])->assertSessionHasErrors('question');
    $this->post(route('faq-admin.entries.store', $category), ['question' => 'Frage', 'answer' => ''])->assertSessionHasErrors('answer');
    $this->post(route('faq-admin.entries.store', $category), ['question' => 'Frage', 'answer' => str_repeat('x', 5001)])->assertSessionHasErrors('answer');
});

it('handles several questions at once: hide, show, move and delete', function () {
    $this->actingAs(fqUser(RoleKey::Administration));
    $category = fqCategory('Zugang beantragen');
    $target = fqCategory('Mitgliedschaft');
    $ids = $category->entries->take(2)->pluck('id')->all();
    $questions = $category->entries->take(2)->pluck('question')->all();
    $route = route('faq-admin.entries.bulk', $category);

    $this->post($route, ['ids' => $ids, 'aktion' => 'deactivate'])->assertRedirect()->assertSessionHas('success');
    expect(FaqEntry::query()->whereIn('id', $ids)->where('is_active', true)->count())->toBe(0);
    $this->get(route('portal.faq'))->assertDontSee($questions[0]);

    $this->post($route, ['ids' => $ids, 'aktion' => 'activate'])->assertRedirect();
    $this->get(route('portal.faq'))->assertSee($questions[0]);

    $this->post($route, ['ids' => $ids, 'aktion' => 'move'])->assertSessionHasErrors('ziel');
    $this->post($route, ['ids' => $ids, 'aktion' => 'move', 'ziel' => $category->id])->assertSessionHasErrors('ziel');
    $this->post($route, ['ids' => $ids, 'aktion' => 'move', 'ziel' => $target->id])->assertRedirect();
    expect(FaqEntry::query()->whereIn('id', $ids)->where('faq_category_id', $target->id)->count())->toBe(2);

    // Fragen anderer Kategorien in der Auswahl bleiben unberührt (sie gehören jetzt nicht mehr zu dieser Kategorie)
    $this->post($route, ['ids' => $ids, 'aktion' => 'delete'])->assertRedirect();
    expect(FaqEntry::query()->whereIn('id', $ids)->count())->toBe(2);

    $this->post(route('faq-admin.entries.bulk', $target), ['ids' => $ids, 'aktion' => 'delete'])->assertRedirect();
    expect(FaqEntry::query()->whereIn('id', $ids)->count())->toBe(0);

    $this->post($route, ['ids' => [], 'aktion' => 'delete'])->assertSessionHasErrors('ids');
});

it('handles several faq categories at once', function () {
    $this->actingAs(fqUser(RoleKey::Administration));

    $ids = FaqCategory::query()->whereIn('title', ['Mitgliedschaft', 'Zugang beantragen'])->pluck('id')->all();

    $this->post(route('faq-admin.bulk'), ['ids' => $ids, 'aktion' => 'deactivate'])->assertRedirect()->assertSessionHas('success');
    expect(FaqCategory::query()->whereIn('id', $ids)->where('is_active', true)->count())->toBe(0);
    $this->get(route('portal.faq'))->assertDontSee('Wie werde ich Mitglied?');

    $this->post(route('faq-admin.bulk'), ['ids' => $ids, 'aktion' => 'activate'])->assertRedirect();
    $this->get(route('portal.faq'))->assertSee('Wie werde ich Mitglied?');

    $this->post(route('faq-admin.bulk'), ['ids' => $ids, 'aktion' => 'delete'])->assertRedirect();
    expect(FaqCategory::query()->whereIn('id', $ids)->count())->toBe(0);

    $this->post(route('faq-admin.bulk'), ['ids' => []])->assertSessionHasErrors('ids');
});

it('keeps inactive questions off the contact topic page and counts only active ones', function () {
    $category = fqCategory('Passwort vergessen oder Anmeldung klappt nicht');
    $topic = \App\Modules\ContactForms\Models\ContactTopic::query()->where('title', 'Passwort vergessen oder Anmeldung klappt nicht')->firstOrFail();
    $hidden = $category->entries->first();

    $hidden->update(['is_active' => false]);

    $this->get(route('portal.contact.topic', $topic))->assertOk()->assertDontSee($hidden->question)->assertSee($category->entries->last()->question);

    // ohne aktive Fragen und ohne „Uns schreiben“ ist das Thema nicht erreichbar
    $category->entries()->update(['is_active' => false]);
    $topic->update(['faq_offer_form' => false]);

    expect($topic->fresh()->isReachable())->toBeFalse();
});
