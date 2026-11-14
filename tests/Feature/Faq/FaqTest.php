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
    $this->get(route('faq-admin.index'))->assertRedirect(route('login'));
    $this->actingAs(fqUser(RoleKey::Team))->get(route('faq-admin.index'))->assertForbidden();
    $this->actingAs(fqUser(RoleKey::Coordination))->get(route('faq-admin.create'))->assertForbidden();

    foreach ([RoleKey::Administration, RoleKey::AdministrationStaff] as $role) {
        $this->actingAs(fqUser($role));
        $this->get(route('faq-admin.index'))->assertOk()->assertSee('Konto, E-Mail-Adresse und Daten');
        $this->get(route('faq-admin.create'))->assertOk();
        $this->get(route('faq-admin.edit', fqCategory('Zugang beantragen')))->assertOk()->assertSee('Wer kann Zugang zum Portal beantragen?');
    }

    $this->actingAs(fqUser(RoleKey::AdministrationStaff))->get(route('office.home'))->assertSee('FAQ');
});

it('creates a category with questions, changes and deletes it', function () {
    $this->actingAs(fqUser(RoleKey::AdministrationStaff));

    $this->post(route('faq-admin.store'), [
        'title' => 'Veranstaltungen', 'description' => 'Rund um Termine', 'sort_order' => 3, 'is_active' => '1', 'in_faq_page' => '1',
        'entries' => [['question' => 'Wo finde ich Termine?', 'answer' => "Im Kalender.\n\n- Eins\n- Zwei"], ['question' => '', 'answer' => ''], ['question' => 'Kostet das etwas?', 'answer' => 'Nein.']],
    ])->assertRedirect(route('faq-admin.index'))->assertSessionHas('success');

    $category = fqCategory('Veranstaltungen');

    expect($category->entries)->toHaveCount(2)->and($category->entries->first()->question)->toBe('Wo finde ich Termine?');

    $this->get(route('portal.faq'))->assertSee('Wo finde ich Termine?')->assertSee('<li>Eins</li>', false);

    $this->put(route('faq-admin.update', $category), ['title' => 'Veranstaltungen und Termine', 'is_active' => '1', 'in_faq_page' => '1', 'entries' => [['question' => 'Nur eine?', 'answer' => 'Ja.']]])->assertRedirect(route('faq-admin.index'));

    expect($category->fresh()->title)->toBe('Veranstaltungen und Termine')->and($category->fresh()->entries)->toHaveCount(1);

    $this->delete(route('faq-admin.destroy', $category))->assertRedirect(route('faq-admin.index'));

    expect(FaqCategory::query()->whereKey($category->id)->exists())->toBeFalse()->and(FaqEntry::query()->where('faq_category_id', $category->id)->count())->toBe(0);
});

it('validates the faq categories', function () {
    $this->actingAs(fqUser(RoleKey::Administration));

    $this->post(route('faq-admin.store'), ['title' => ''])->assertSessionHasErrors('title');
    $this->post(route('faq-admin.store'), ['title' => 'A', 'entries' => [['question' => 'Nur Frage', 'answer' => '']]])->assertSessionHasErrors('entries');
    $this->post(route('faq-admin.store'), ['title' => 'A', 'entries' => [['question' => '', 'answer' => 'Nur Antwort']]])->assertSessionHasErrors('entries');
    $this->post(route('faq-admin.store'), ['title' => str_repeat('x', 151)])->assertSessionHasErrors('title');
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
