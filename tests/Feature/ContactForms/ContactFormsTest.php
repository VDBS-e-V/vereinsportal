<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\ContactForms\Mail\ContactFormMail;
use App\Modules\ContactForms\Models\ContactFaq;
use App\Modules\ContactForms\Models\ContactForm;
use App\Modules\ContactForms\Models\ContactTopic;
use App\Modules\ContactForms\Support\ContactFormFields;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Mail::fake();
});

function cfUser(?RoleKey $role): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

function cfPerson(array $overrides = []): array
{
    return $overrides + ['first_name' => 'Erika', 'last_name' => 'Muster', 'email' => 'Erika@Beispiel.example', 'consent' => '1'];
}

function cfTopic(string $title): ContactTopic
{
    return ContactTopic::query()->where('title', $title)->firstOrFail();
}

// ---------- Themenwahl ----------

it('searches the topics by title, description and keywords', function () {
    $html = $this->get(route('portal.contact', ['q' => 'passwort']))->assertOk()->getContent();

    expect($html)->toContain('Passwort vergessen oder Anmeldung klappt nicht')->not->toContain('Frage an den Vorstand');

    $this->get(route('portal.contact', ['q' => 'gibtesnicht']))->assertOk()->assertSee('Dazu gibt es kein Thema');
    $this->get(route('portal.contact', ['q' => 'Telefon Rückruf']))->assertOk()->assertSee('Rückruf erbeten');
});

it('lists the topics of one area', function () {
    $area = cfTopic('Verein und Mitgliedschaft');

    $this->get(route('portal.contact', ['bereich' => $area->id]))->assertOk()->assertSee('Mitglied werden')->assertSee('Frage an den Vorstand')->assertDontSee('Passwort vergessen');
});

it('hides topics that are switched off or have no target', function () {
    cfTopic('Frage an den Vorstand')->update(['is_active' => false]);
    ContactForm::query()->where('slug', 'portal-support')->update(['is_active' => false]);

    $html = $this->get(route('portal.contact', ['q' => 'problem']))->assertOk()->getContent();

    expect($html)->not->toContain('Technisches Problem im Portal');

    $this->get(route('portal.contact', ['q' => 'vorstand']))->assertOk()->assertDontSee('Frage an den Vorstand');
    $this->get(route('portal.contact.topic', cfTopic('Frage an den Vorstand')))->assertNotFound();
});

it('leads each kind of topic to its target', function () {
    $this->get(route('portal.contact.topic', cfTopic('Frage an den Vorstand')))->assertRedirect(route('portal.contact.form', ['form' => 'vorstand', 'thema' => cfTopic('Frage an den Vorstand')->id]));
    $this->get(route('portal.contact.topic', cfTopic('Mein Thema ist nicht dabei')))->assertRedirect(route('portal.contact.form', ['form' => 'allgemeine-anfrage', 'thema' => cfTopic('Mein Thema ist nicht dabei')->id]));
    $this->get(route('portal.contact.topic', cfTopic('Mitglied werden')))->assertRedirect('/mitglied-werden');
    $this->get(route('portal.contact.topic', cfTopic('Verein und Mitgliedschaft')))->assertRedirect(route('portal.contact', ['bereich' => cfTopic('Verein und Mitgliedschaft')->id]));
});

it('shows a faq page with questions, answers and the way to the form', function () {
    $topic = cfTopic('Passwort vergessen oder Anmeldung klappt nicht');

    $html = $this->get(route('portal.contact.topic', $topic))->assertOk()->getContent();

    expect($html)->toContain('Wie setze ich mein Passwort zurück?')->toContain('Ihre Frage ist nicht dabei?')
        ->toContain(route('portal.contact.form', ['form' => 'portal-support', 'thema' => $topic->id]));

    $topic->update(['faq_offer_form' => false]);

    $this->get(route('portal.contact.topic', $topic))->assertOk()->assertDontSee('Ihre Frage ist nicht dabei?');
});

it('never follows a link topic to an unsafe address', function () {
    $topic = cfTopic('Mitglied werden');

    $topic->update(['target_url' => '//boese.example']);
    $this->get(route('portal.contact.topic', $topic))->assertNotFound();

    $topic->update(['target_url' => 'https://www.beispiel.example/seite']);
    $this->get(route('portal.contact.topic', $topic))->assertRedirect('https://www.beispiel.example/seite');
});

it('points to the standard form when there are no topics', function () {
    ContactTopic::query()->delete();

    $this->get(route('portal.contact'))->assertOk()->assertSee('Zurzeit keine Themen')->assertSee(route('portal.contact.form', ['form' => 'allgemeine-anfrage']), false);
});

// ---------- Formulare ----------

it('asks only the personal data a data-only form needs and sends the answers by mail', function () {
    $html = $this->get(route('portal.contact.form', ['form' => 'rueckruf']))->assertOk()->getContent();

    expect($html)->toContain('name="phone"')->toContain('name="answers[anliegen]"')->toContain('name="answers[erreichbarkeit]"')->not->toContain('name="message"')->not->toContain('name="subject"')->not->toContain('name="organization"');

    $this->post(route('portal.contact.send', ['form' => 'rueckruf']), cfPerson(['phone' => '0151 1234567', 'answers' => ['anliegen' => 'Frage zum Seminar', 'erreichbarkeit' => 'nach 15 Uhr']]))
        ->assertRedirect(route('portal.contact'))->assertSessionHas('success', 'Danke, wir rufen Sie zurück.');

    Mail::assertSent(ContactFormMail::class, fn (ContactFormMail $mail): bool => $mail->hasTo('kontakt@vdb.schule')
        && $mail->hasReplyTo('erika@beispiel.example')
        && $mail->person['phone'] === '0151 1234567'
        && $mail->subjectLine === null
        && $mail->answers === [['label' => 'Worum geht es kurz?', 'value' => 'Frage zum Seminar'], ['label' => 'Wann sind Sie erreichbar?', 'value' => 'nach 15 Uhr']]
        && str_contains($mail->envelope()->subject, 'Rückruf erbeten'));
});

it('validates required personal data and custom fields', function () {
    $route = route('portal.contact.send', ['form' => 'rueckruf']);

    $this->post($route, cfPerson())->assertSessionHasErrors(['phone', 'answers.anliegen']);
    $this->post($route, cfPerson(['phone' => 'abc', 'answers' => ['anliegen' => 'x']]))->assertSessionHasErrors('phone');
    $this->post($route, cfPerson(['phone' => '0151 1234567', 'answers' => ['anliegen' => 'x'], 'consent' => null]))->assertSessionHasErrors('consent');
    // ein Feld, das das Formular nicht abfragt, wird abgewiesen
    $this->post(route('portal.contact.send', ['form' => 'vorstand']), cfPerson(['phone' => '0151 1234567', 'subject' => 'Frage', 'message' => 'Text']))->assertSessionHasErrors('phone');

    Mail::assertNothingSent();
});

it('supports all kinds of custom fields and a copy for the sender', function () {
    $form = ContactForm::query()->create([
        'slug' => 'test', 'name' => 'Testformular', 'kind' => 'data', 'recipient_email' => 'test@beispiel.example', 'recipient_label' => 'Test', 'send_copy' => true,
        'fields' => ContactFormFields::normalize([
            ['label' => 'Schulform', 'type' => 'select', 'required' => '1', 'options' => "Grundschule\nGymnasium"],
            ['label' => 'Wunschtermin', 'type' => 'date'],
            ['label' => 'Ich bin einverstanden mit dem Newsletter', 'type' => 'checkbox'],
        ]),
    ]);

    $route = route('portal.contact.send', ['form' => $form->slug]);

    $this->post($route, cfPerson(['answers' => ['schulform' => 'Realschule']]))->assertSessionHasErrors('answers.schulform');
    $this->post($route, cfPerson(['answers' => ['schulform' => 'Gymnasium', 'wunschtermin' => 'kein Datum']]))->assertSessionHasErrors('answers.wunschtermin');

    $this->post($route, cfPerson(['answers' => ['schulform' => 'Gymnasium', 'wunschtermin' => '2027-03-01', 'ich_bin_einverstanden_mit_dem_newsletter' => '1']]))->assertSessionHasNoErrors();

    Mail::assertSentCount(2);
    Mail::assertSent(ContactFormMail::class, fn (ContactFormMail $mail): bool => ! $mail->copy && $mail->hasTo('test@beispiel.example') && $mail->answers[2]['value'] === 'Ja');
    Mail::assertSent(ContactFormMail::class, fn (ContactFormMail $mail): bool => $mail->copy && $mail->hasTo('erika@beispiel.example') && str_starts_with($mail->envelope()->subject, 'Ihre Kopie'));
});

it('turns the rows of the editor into clean fields', function () {
    $fields = ContactFormFields::normalize([
        ['label' => '  ', 'type' => 'text'],
        ['label' => 'Schulname', 'type' => 'ungueltig', 'required' => '1'],
        ['label' => 'Schulname', 'type' => 'select', 'options' => "A\n\nB\nA", 'key' => 'schulname'],
        'kaputt',
    ]);

    expect($fields)->toHaveCount(2)
        ->and($fields[0]['type'])->toBe('text')->and($fields[0]['required'])->toBeTrue()->and($fields[0]['key'])->toBe('schulname')
        ->and($fields[1]['key'])->toBe('schulname_2')->and($fields[1]['options'])->toBe(['A', 'B']);
});

// ---------- Verwaltung ----------

it('keeps the administration to administration and the office', function () {
    $this->get(route('contact-admin.topics.index'))->assertRedirect(route('login'));
    $this->actingAs(cfUser(RoleKey::Team))->get(route('contact-admin.topics.index'))->assertForbidden();
    $this->actingAs(cfUser(RoleKey::Coordination))->get(route('contact-admin.forms.index'))->assertForbidden();

    foreach ([RoleKey::Administration, RoleKey::AdministrationStaff] as $role) {
        $this->actingAs(cfUser($role));
        $this->get(route('contact-admin.topics.index'))->assertOk()->assertSee('Passwort vergessen oder Anmeldung klappt nicht');
        $this->get(route('contact-admin.forms.index'))->assertOk()->assertSee('Allgemeine Anfrage');
        $this->get(route('contact-admin.topics.create'))->assertOk();
        $this->get(route('contact-admin.forms.create'))->assertOk();
    }

    $this->actingAs(cfUser(RoleKey::AdministrationStaff))->get(route('office.home'))->assertSee('Kontaktformular');
});

it('creates a topic with faq entries and shows it in the topic selection', function () {
    $this->actingAs(cfUser(RoleKey::AdministrationStaff));

    $area = cfTopic('Sonstiges');

    $this->post(route('contact-admin.topics.store'), [
        'title' => 'Wo finde ich Materialien?', 'description' => 'Antworten rund um Materialien', 'keywords' => 'download arbeitsblatt', 'action' => 'faq', 'parent_id' => $area->id, 'sort_order' => 5, 'is_active' => '1', 'faq_offer_form' => '1',
        'faqs' => [['question' => 'Wo liegen die Dateien?', 'answer' => 'Im Bereich Mitglieder unter Dokumente.'], ['question' => '', 'answer' => ''], ['question' => 'Kosten sie etwas?', 'answer' => 'Nein.']],
    ])->assertRedirect(route('contact-admin.topics.index'))->assertSessionHas('success');

    $topic = cfTopic('Wo finde ich Materialien?');

    expect($topic->action)->toBe('faq')->and($topic->parent_id)->toBe($area->id)->and($topic->faqs)->toHaveCount(2)
        ->and($topic->faqs->first()->question)->toBe('Wo liegen die Dateien?');

    $this->get(route('portal.contact', ['q' => 'arbeitsblatt']))->assertSee('Wo finde ich Materialien?');
    $this->get(route('portal.contact.topic', $topic))->assertOk()->assertSee('Im Bereich Mitglieder unter Dokumente.');

    $this->put(route('contact-admin.topics.update', $topic), ['title' => 'Wo finde ich Materialien?', 'action' => 'faq', 'is_active' => '1', 'faqs' => [['question' => 'Nur eine Frage', 'answer' => 'Eine Antwort']]])->assertRedirect();

    expect($topic->fresh()->faqs)->toHaveCount(1)->and(ContactFaq::query()->where('contact_topic_id', $topic->id)->count())->toBe(1);
});

it('validates the topics', function () {
    $this->actingAs(cfUser(RoleKey::Administration));

    $this->post(route('contact-admin.topics.store'), ['title' => 'A', 'action' => 'form'])->assertSessionHasErrors('contact_form_id');
    $this->post(route('contact-admin.topics.store'), ['title' => 'A', 'action' => 'link', 'target_url' => 'http://unsicher.example'])->assertSessionHasErrors('target_url');
    $this->post(route('contact-admin.topics.store'), ['title' => 'A', 'action' => 'faq', 'faqs' => [['question' => 'Nur Frage', 'answer' => '']]])->assertSessionHasErrors('faqs');
    $this->post(route('contact-admin.topics.store'), ['title' => 'A', 'action' => 'category', 'parent_id' => cfTopic('Sonstiges')->id])->assertSessionHasErrors('parent_id');
    $this->post(route('contact-admin.topics.store'), ['title' => '', 'action' => 'default'])->assertSessionHasErrors('title');

    // ein Bereich mit Themen bleibt ein Bereich
    $this->put(route('contact-admin.topics.update', cfTopic('Sonstiges')), ['title' => 'Sonstiges', 'action' => 'default'])->assertSessionHasErrors('action');
});

it('handles several topics at once and keeps areas that still have topics', function () {
    $this->actingAs(cfUser(RoleKey::Administration));

    $topics = ContactTopic::query()->whereIn('title', ['Mitglied werden', 'Frage an den Vorstand'])->pluck('id')->all();

    $this->post(route('contact-admin.topics.bulk'), ['ids' => $topics, 'aktion' => 'deactivate'])->assertRedirect()->assertSessionHas('success');
    expect(ContactTopic::query()->whereIn('id', $topics)->where('is_active', true)->count())->toBe(0);

    $this->post(route('contact-admin.topics.bulk'), ['ids' => $topics, 'aktion' => 'activate'])->assertRedirect();
    expect(ContactTopic::query()->whereIn('id', $topics)->where('is_active', true)->count())->toBe(2);

    $area = cfTopic('Zugang und Konto');
    $this->post(route('contact-admin.topics.bulk'), ['ids' => [...$topics, $area->id], 'aktion' => 'delete'])->assertRedirect()->assertSessionHas('success');

    expect(ContactTopic::query()->whereIn('id', $topics)->count())->toBe(0)->and(ContactTopic::query()->whereKey($area->id)->exists())->toBeTrue();

    $this->delete(route('contact-admin.topics.destroy', $area))->assertSessionHas('error');
    $this->post(route('contact-admin.topics.bulk'), ['ids' => []])->assertSessionHasErrors('ids');
});

it('creates and changes a form with custom fields in the editor', function () {
    $this->actingAs(cfUser(RoleKey::AdministrationStaff));

    $this->post(route('contact-admin.forms.store'), [
        'name' => 'Seminaranfrage', 'description' => 'Für Anfragen zu Seminaren', 'kind' => 'message', 'recipient_email' => 'Seminare@Beispiel.example', 'recipient_label' => 'Koordination',
        'ask_phone' => 'optional', 'ask_organization' => 'required', 'is_active' => '1', 'send_copy' => '1',
        'fields' => [['label' => 'Schulform', 'type' => 'select', 'options' => "Grundschule\nGymnasium", 'required' => '1', 'key' => ''], ['label' => '', 'type' => 'text']],
    ])->assertRedirect(route('contact-admin.forms.index'))->assertSessionHas('success');

    $form = ContactForm::query()->where('slug', 'seminaranfrage')->firstOrFail();

    expect($form->recipient_email)->toBe('seminare@beispiel.example')->and($form->send_copy)->toBeTrue()->and($form->is_default)->toBeFalse()
        ->and($form->customFields())->toHaveCount(1)->and($form->customFields()[0]['options'])->toBe(['Grundschule', 'Gymnasium'])->and($form->customFields()[0]['required'])->toBeTrue();

    $this->get(route('portal.contact.form', ['form' => 'seminaranfrage']))->assertOk()->assertSee('name="organization"', false)->assertSee('Gymnasium');

    $this->put(route('contact-admin.forms.update', $form), ['name' => 'Seminaranfrage neu', 'kind' => 'message', 'recipient_email' => 'seminare@beispiel.example', 'recipient_label' => 'Koordination', 'ask_phone' => 'no', 'ask_organization' => 'no', 'is_active' => '1'])->assertRedirect();

    expect($form->fresh()->name)->toBe('Seminaranfrage neu')->and($form->fresh()->slug)->toBe('seminaranfrage')->and($form->fresh()->customFields())->toBe([]);
});

it('validates the forms', function () {
    $this->actingAs(cfUser(RoleKey::Administration));
    $base = ['name' => 'X', 'kind' => 'message', 'recipient_email' => 'x@beispiel.example', 'recipient_label' => 'X', 'ask_phone' => 'no', 'ask_organization' => 'no'];

    $this->post(route('contact-admin.forms.store'), ['recipient_email' => 'kaputt'] + $base)->assertSessionHasErrors('recipient_email');
    $this->post(route('contact-admin.forms.store'), ['kind' => 'data'] + $base)->assertSessionHasErrors('kind');
    $this->post(route('contact-admin.forms.store'), ['fields' => [['label' => 'Liste', 'type' => 'select', 'options' => '']]] + $base)->assertSessionHasErrors('fields');
    $this->post(route('contact-admin.forms.store'), ['ask_phone' => 'immer'] + $base)->assertSessionHasErrors('ask_phone');
});

it('makes another form the standard and protects the standard form from deleting', function () {
    $this->actingAs(cfUser(RoleKey::Administration));

    $standard = ContactForm::standard();
    $board = ContactForm::query()->where('slug', 'vorstand')->firstOrFail();

    expect($standard->slug)->toBe('allgemeine-anfrage');

    $this->delete(route('contact-admin.forms.destroy', $standard))->assertSessionHas('error');
    $this->post(route('contact-admin.forms.destroy-many'), ['ids' => [$standard->id]])->assertRedirect();
    expect(ContactForm::query()->whereKey($standard->id)->exists())->toBeTrue();

    $this->post(route('contact-admin.forms.default', $board))->assertSessionHas('success');

    expect(ContactForm::standard()->slug)->toBe('vorstand')->and(ContactForm::query()->where('is_default', true)->count())->toBe(1);

    $this->post(route('contact-admin.forms.destroy-many'), ['ids' => [$standard->id]])->assertRedirect();
    expect(ContactForm::query()->whereKey($standard->id)->exists())->toBeFalse();

    // Themen, die zum gelöschten Formular führten, sind verborgen statt kaputt
    $this->get(route('portal.contact.topic', cfTopic('Mein Thema ist nicht dabei')))->assertRedirect();
    $this->get(route('portal.contact', ['q' => 'Rückruf']))->assertOk();
});

it('hides topics of a deleted form instead of breaking', function () {
    $this->actingAs(cfUser(RoleKey::Administration));
    $support = ContactForm::query()->where('slug', 'portal-support')->firstOrFail();

    $this->delete(route('contact-admin.forms.destroy', $support))->assertRedirect(route('contact-admin.forms.index'));

    $this->get(route('portal.contact', ['q' => 'technisches']))->assertOk()->assertDontSee('Technisches Problem im Portal');
    $this->get(route('portal.contact.topic', cfTopic('Technisches Problem im Portal')))->assertNotFound();
    $this->get(route('contact-admin.topics.index'))->assertOk()->assertSee('ohne Ziel');
});

// ---------- Große FAQ-Seite ----------

it('shows all faq entries on one page, grouped by area, with the answers formatted safely', function () {
    $html = $this->get(route('portal.faq'))->assertOk()->getContent();

    expect($html)->toContain('Häufig gestellte Fragen')
        ->toContain('Allgemeines zum Portal')->toContain('Zugang und Konto')->toContain('Schulen und Workshops')->toContain('Datenschutz und Rechtliches')
        ->toContain('Was ist das Vereinsportal und wofür wurde es entwickelt?')->toContain('Wie setze ich mein Passwort zurück?')->toContain('Wie kann ich Teamer:in werden?')
        // Auszeichnung: Liste, fett und Link auf einen Pfad im Portal
        ->toContain('<li><strong>Teamer:innen:</strong>')->toContain('<a href="/passwort-vergessen">Passwort vergessen</a>')
        ->toContain('href="#bereich-')->toContain('Uns schreiben');
});

it('searches the questions and answers', function () {
    $html = $this->get(route('portal.faq', ['q' => 'authenticator']))->assertOk()->getContent();

    expect($html)->toContain('Ich habe das Gerät mit meiner Authenticator-App verloren.')->toContain('Was ist die Zwei-Faktor-Anmeldung?')->not->toContain('Wie kann ich Teamer:in werden?');

    $this->get(route('portal.faq', ['q' => 'gibtesnicht']))->assertOk()->assertSee('Nichts gefunden');
});

it('keeps hidden and switched-off topics off the faq page', function () {
    cfTopic('Teamarbeit, Verträge und Abrechnung')->update(['in_faq_page' => false]);
    cfTopic('Mitgliedschaft')->update(['is_active' => false]);
    cfTopic('Schulen und Workshops')->update(['is_active' => false]);

    $html = $this->get(route('portal.faq'))->assertOk()->getContent();

    expect($html)->not->toContain('Wie kann ich Teamer:in werden?')->not->toContain('Wie werde ich Mitglied?')->not->toContain('Wie frage ich ein Angebot an?')->toContain('Wie setze ich mein Passwort zurück?');
});

it('escapes html in the answers and allows only safe links', function () {
    $html = \App\Modules\ContactForms\Support\FaqText::html("<script>alert(1)</script> **fett**\n\n- Eins\n- [Intern](/kontakt)\n\n[Böse](javascript:alert(1)) [Auch böse](//boese.example) [Gut](https://beispiel.example/a?x=1&y=2)")->toHtml();

    expect($html)->not->toContain('<script>')->toContain('&lt;script&gt;')->toContain('<strong>fett</strong>')->toContain('<ul><li>Eins</li><li><a href="/kontakt">Intern</a></li></ul>')
        ->not->toContain('href="javascript:')->not->toContain('href="//boese.example"')->toContain('<a href="https://beispiel.example/a?x=1&amp;y=2">Gut</a>');
});

it('lets the office decide which topics feed the faq page', function () {
    $this->actingAs(cfUser(RoleKey::AdministrationStaff));
    $topic = cfTopic('Mitgliedschaft');

    $this->put(route('contact-admin.topics.update', $topic), ['title' => 'Mitgliedschaft', 'action' => 'faq', 'is_active' => '1', 'faqs' => [['question' => 'Neue Frage?', 'answer' => 'Neue Antwort.']]])->assertRedirect();

    expect($topic->fresh()->in_faq_page)->toBeFalse();
    $this->get(route('portal.faq'))->assertDontSee('Neue Frage?');

    $this->put(route('contact-admin.topics.update', $topic), ['title' => 'Mitgliedschaft', 'action' => 'faq', 'is_active' => '1', 'in_faq_page' => '1', 'faqs' => [['question' => 'Neue Frage?', 'answer' => 'Neue Antwort.']]])->assertRedirect();

    $this->get(route('portal.faq'))->assertSee('Neue Frage?');
});
