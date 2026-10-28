<?php

use App\Support\Forms\FieldState;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

function form(string $markup): string
{
    return Blade::render($markup);
}

function shareErrors(array $messages): void
{
    View::share('errors', (new ViewErrorBag)->put('default', new MessageBag($messages)));
}

afterEach(function () {
    View::share('errors', new ViewErrorBag);
});

/* ---------- FieldState ---------- */

it('builds ids, dotted names and option ids by convention', function () {
    expect(FieldState::dotted('user[email]'))->toBe('user.email')
        ->and(FieldState::dotted('tags[]'))->toBe('tags')
        ->and(FieldState::idFor('user[email]'))->toBe('field-user-email')
        ->and(FieldState::idFor('email'))->toBe('field-email')
        ->and(FieldState::optionId('role', 'Mit glied'))->toBe('field-role-mit-glied');
});

it('collects describedby ids for hint, error and success', function () {
    $state = FieldState::make('email', hint: 'Hinweis', error: 'Fehler');

    expect($state->describedBy())->toBe('field-email-hint field-email-error')
        ->and($state->describedBy('extra'))->toBe('field-email-hint field-email-error extra')
        ->and(FieldState::make('email', success: 'Ok')->describedBy())->toBe('field-email-success')
        ->and(FieldState::make('email')->describedBy())->toBeNull();
});

it('prefers an explicit error over the validation bag and ignores success on errors', function () {
    $bag = (new ViewErrorBag)->put('default', new MessageBag(['email' => ['Aus dem Bag']]));

    expect(FieldState::make('email', errors: $bag)->error)->toBe('Aus dem Bag')
        ->and(FieldState::make('email', error: 'Explizit', errors: $bag)->error)->toBe('Explizit')
        ->and(FieldState::make('email', error: 'x', success: 'ok')->hasSuccess())->toBeFalse();
});

/* ---------- Input ---------- */

it('renders an input with label, id and required flag as text', function () {
    $html = form('<x-vdbs.forms.input name="email" label="E-Mail" type="email" :required="true" autocomplete="email" />');

    expect($html)
        ->toContain('<label class="vdbs-field__label" for="field-email">')
        ->toContain('id="field-email"')
        ->toContain('name="email"')
        ->toContain('type="email"')
        ->toContain('autocomplete="email"')
        ->toContain(' required')
        ->toContain('(Pflichtfeld)')
        ->toContain('vdbs-control--size-m');
});

it('marks optional fields with text and falls back for unknown types and sizes', function () {
    $html = form('<x-vdbs.forms.input name="x" label="X" type="hacker" size="riesig" :optional="true" />');

    expect($html)->toContain('(optional)')->toContain('type="text"')->toContain('vdbs-control--size-m');
});

it('wires hint and error to the field for assistive technology', function () {
    $html = form('<x-vdbs.forms.input name="email" label="E-Mail" hint="Ihre Adresse" error="Ungültig" />');

    expect($html)
        ->toContain('id="field-email-hint"')
        ->toContain('id="field-email-error"')
        ->toContain('aria-describedby="field-email-hint field-email-error"')
        ->toContain('aria-invalid="true"')
        ->toContain('vdbs-control--error')
        ->toContain('Fehler: ')
        ->toContain('Ungültig');
});

it('shows a success message with icon and text', function () {
    $html = form('<x-vdbs.forms.input name="u" label="Name" success="Frei" />');

    expect($html)->toContain('vdbs-field__message--success')->toContain('vdbs-control--success')->toContain('Frei')->not->toContain('aria-invalid');
});

it('takes the error message from the validation errors of the page', function () {
    shareErrors(['email' => ['Das ist keine E-Mail-Adresse.']]);

    expect(form('<x-vdbs.forms.input name="email" label="E-Mail" />'))
        ->toContain('Das ist keine E-Mail-Adresse.')
        ->toContain('aria-invalid="true"');
});

it('renders prefix, suffix and icons as an input group', function () {
    $html = form('<x-vdbs.forms.input name="p" label="Betrag" prefix="€" suffix="/ Monat" prefix-icon="mail" />');

    expect($html)->toContain('vdbs-input-group')->toContain('€')->toContain('/ Monat')->toContain('<svg')->toContain('vdbs-control--bare');
});

it('adds a character counter only with counter and maxlength', function () {
    expect(form('<x-vdbs.forms.input name="a" label="A" maxlength="80" :counter="true" value="abc" />'))
        ->toContain('data-vdbs-counter="field-a"')->toContain('3 / 80')
        ->and(form('<x-vdbs.forms.input name="a" label="A" :counter="true" />'))->not->toContain('data-vdbs-counter');
});

it('hides the label visually but keeps it for screen readers', function () {
    expect(form('<x-vdbs.forms.input name="q" label="Suche" :label-hidden="true" />'))->toContain('vdbs-field__label vdbs-sr-only');
});

it('puts the class on the field wrapper and other attributes on the control', function () {
    $html = form('<x-vdbs.forms.input name="z" label="Z" class="vdbs-form-grid__full" data-test="1" />');

    expect($html)->toContain('class="vdbs-field vdbs-form-grid__full"')->toContain('data-test="1"');
});

/* ---------- Password, code ---------- */

it('renders the password field with a script-only toggle and the right autocomplete', function () {
    $current = form('<x-vdbs.forms.password />');
    $new = form('<x-vdbs.forms.password name="pw" :new="true" :rules="[\'12 Zeichen\']" />');

    expect($current)->toContain('type="password"')->toContain('autocomplete="current-password"')->toContain('vdbs-js-only')->toContain('data-vdbs-password-toggle')->toContain('aria-pressed="false"')
        ->and($new)->toContain('autocomplete="new-password"')->toContain('12 Zeichen')->toContain('aria-describedby="field-pw-rules"');
});

it('renders a one-time code field with a numeric pattern', function () {
    $html = form('<x-vdbs.forms.code name="code" />');

    expect($html)
        ->toContain('autocomplete="one-time-code"')
        ->toContain('inputmode="numeric"')
        ->toContain('pattern="[0-9]{6}"')
        ->toContain('maxlength="6"')
        ->toContain('data-vdbs-code="numeric"')
        ->toContain('--code-length: 6');
});

it('limits the code length and supports alphanumeric codes', function () {
    expect(form('<x-vdbs.forms.code :length="99" />'))->toContain('maxlength="10"')
        ->and(form('<x-vdbs.forms.code :length="1" />'))->toContain('maxlength="4"')
        ->and(form('<x-vdbs.forms.code kind="alphanumeric" :length="8" />'))->toContain('inputmode="text"')->toContain('pattern="[A-Za-z0-9]{8}"');
});

/* ---------- Textarea, select ---------- */

it('renders a textarea with content from the slot and a counter', function () {
    $html = form('<x-vdbs.forms.textarea name="t" label="Text" maxlength="50" :counter="true" :rows="3">Hallo</x-vdbs.forms.textarea>');

    expect($html)->toContain('<textarea')->toContain('rows="3"')->toContain('>Hallo</textarea>')->toContain('5 / 50');
});

it('renders a select with placeholder, selected option and groups', function () {
    $plain = form('<x-vdbs.forms.select name="s" label="S" placeholder="Bitte wählen" value="b" :options="[\'a\' => \'A\', \'b\' => \'B\']" />');
    $grouped = form('<x-vdbs.forms.select name="g" label="G" :options="[\'Gruppe\' => [\'x\' => \'X\']]" />');

    expect($plain)->toContain('<option value="" disabled')->toContain('Bitte wählen')->toContain('<option value="b" selected')->toContain('vdbs-select__arrow')
        ->and($grouped)->toContain('<optgroup label="Gruppe">');
});

it('renders a multiple select as array field with several selections', function () {
    $html = form('<x-vdbs.forms.select name="m" label="M" :multiple="true" :value="[\'a\', \'c\']" :options="[\'a\' => \'A\', \'b\' => \'B\', \'c\' => \'C\']" />');

    expect($html)->toContain('name="m[]"')->toContain('multiple')->toContain('<option value="a" selected')->toContain('<option value="c" selected')->not->toContain('<option value="b" selected');
});

/* ---------- Auswahlfelder ---------- */

it('renders a checkbox with label, hint and state', function () {
    $html = form('<x-vdbs.forms.checkbox name="remember" label="Merken" hint="Auf diesem Gerät" :checked="true" :required="true" />');

    expect($html)
        ->toContain('type="checkbox"')
        ->toContain('<label class="vdbs-choice__label" for="field-remember">')
        ->toContain('checked')
        ->toContain('aria-describedby="field-remember-hint"')
        ->toContain('(Pflichtfeld)');
});

it('shows checkbox errors with aria-invalid and the message', function () {
    $html = form('<x-vdbs.forms.checkbox name="ok" label="Zustimmen" error="Bitte zustimmen" />');

    expect($html)->toContain('aria-invalid="true"')->toContain('Bitte zustimmen')->toContain('vdbs-choice--error');
});

it('supports the indeterminate state', function () {
    expect(form('<x-vdbs.forms.checkbox name="all" label="Alle" :indeterminate="true" />'))->toContain('data-vdbs-indeterminate');
});

it('renders a checkbox group as fieldset with array names and preselected values', function () {
    $html = form('<x-vdbs.forms.checkbox-group name="areas" legend="Bereiche" :value="[\'b\']" :options="[\'a\' => \'A\', \'b\' => [\'label\' => \'B\', \'hint\' => \'Mit Hinweis\'], \'c\' => [\'label\' => \'C\', \'disabled\' => true]]" />');

    expect($html)
        ->toContain('<fieldset')
        ->toContain('<legend')
        ->toContain('name="areas[]"')
        ->toContain('id="field-areas-a"')
        ->toContain('Mit Hinweis')
        ->toContain('aria-describedby="field-areas-b-hint"')
        ->toContain('disabled');

    expect(preg_match('/id="field-areas-b"[^>]*checked/', $html))->toBe(1)
        ->and(preg_match('/id="field-areas-a"[^>]*checked/', $html))->toBe(0);
});

it('renders a radio group with one preselected option, layouts and an error', function () {
    $html = form('<x-vdbs.forms.radio-group name="role" legend="Rolle" value="team" layout="cards" :required="true" error="Bitte wählen" :options="[\'member\' => \'Mitglied\', \'team\' => \'Team\']" />');

    expect($html)
        ->toContain('type="radio"')
        ->toContain('vdbs-choice-group--cards')
        ->toContain('Bitte wählen')
        ->toContain('(Pflichtfeld)')
        ->toContain('id="field-role"');

    expect(preg_match('/id="field-role-team"[^>]*checked/', $html))->toBe(1)
        ->and(preg_match('/id="field-role-member"[^>]*checked/', $html))->toBe(0)
        ->and(form('<x-vdbs.forms.radio-group name="r" legend="R" layout="weird" :options="[\'a\' => \'A\']" />'))->toContain('vdbs-choice-group--list');
});

it('renders a switch as a checkbox with role switch and a text state', function () {
    $html = form('<x-vdbs.forms.switch name="mail" label="Benachrichtigungen" :checked="true" />');

    expect($html)->toContain('role="switch"')->toContain('type="checkbox"')->toContain('vdbs-choice__state-on')->toContain('checked');
});

/* ---------- Upload, Suche ---------- */

it('renders a file upload with accept, multiple and a file list', function () {
    $html = form('<x-vdbs.forms.file name="docs" label="Dateien" accept=".pdf" :multiple="true" hint="Max. 5 MB" />');

    expect($html)
        ->toContain('type="file"')
        ->toContain('name="docs[]"')
        ->toContain('accept=".pdf"')
        ->toContain('multiple')
        ->toContain('data-vdbs-file-list')
        ->toContain('aria-describedby="field-docs-hint field-docs-files"');
});

it('renders the dropzone variant with a prompt', function () {
    expect(form('<x-vdbs.forms.file name="d" label="D" variant="dropzone" />'))->toContain('vdbs-file--dropzone')->toContain('Datei hierher ziehen');
});

it('renders a search form with role search and a hidden label', function () {
    $html = form('<x-vdbs.forms.search name="q" label="Portal durchsuchen" action="/suche" />');

    expect($html)->toContain('role="search"')->toContain('method="get"')->toContain('action="/suche"')->toContain('type="search"')->toContain('<label class="vdbs-sr-only" for="field-q">Portal durchsuchen</label>')->not->toContain('csrf');
});

/* ---------- Formular, Fehlerübersicht ---------- */

it('renders an error summary with jump links by id convention', function () {
    $html = form('<x-vdbs.forms.error-summary :errors="[\'email\' => \'Ungültige E-Mail\', \'user[name]\' => \'Name fehlt\']" />');

    expect($html)
        ->toContain('role="alert"')
        ->toContain('data-vdbs-error-summary')
        ->toContain('href="#field-email"')
        ->toContain('href="#field-user-name"')
        ->toContain('Ungültige E-Mail');
});

it('renders nothing without errors and reads the validation bag of the page', function () {
    expect(trim(form('<x-vdbs.forms.error-summary />')))->toBe('');

    shareErrors(['email' => ['Falsch'], 'password' => ['Fehlt']]);

    expect(form('<x-vdbs.forms.error-summary title="Anmeldung nicht möglich" />'))->toContain('Anmeldung nicht möglich')->toContain('#field-email')->toContain('#field-password');
});

it('renders a post form with csrf, novalidate, summary and grouped actions', function () {
    shareErrors(['email' => ['Falsch']]);

    $html = form('<x-vdbs.forms.form action="/x">Inhalt<x-slot:actions>Aktion</x-slot:actions></x-vdbs.forms.form>');

    expect($html)
        ->toContain('method="post"')
        ->toContain('action="/x"')
        ->toContain('name="_token"')
        ->toContain('novalidate')
        ->toContain('vdbs-error-summary')
        ->toContain('vdbs-form__actions')
        ->toContain('role="group"');
});

it('spoofs put, patch and delete and leaves get forms without csrf', function () {
    expect(form('<x-vdbs.forms.form method="put" action="/x">x</x-vdbs.forms.form>'))->toContain('name="_method" value="PUT"')
        ->and(form('<x-vdbs.forms.form method="DELETE" action="/x">x</x-vdbs.forms.form>'))->toContain('value="DELETE"')
        ->and(form('<x-vdbs.forms.form method="get" action="/x">x</x-vdbs.forms.form>'))->toContain('method="get"')->not->toContain('_token');
});

it('sets the upload encoding and can switch off the summary', function () {
    shareErrors(['a' => ['b']]);

    $html = form('<x-vdbs.forms.form action="/x" :files="true" :summary="false">x</x-vdbs.forms.form>');

    expect($html)->toContain('enctype="multipart/form-data"')->not->toContain('vdbs-error-summary');
});

it('renders grid columns, sections and fieldsets with accessible names', function () {
    expect(form('<x-vdbs.forms.form-grid :columns="3">x</x-vdbs.forms.form-grid>'))->toContain('vdbs-form-grid--3')
        ->and(form('<x-vdbs.forms.form-grid :columns="9">x</x-vdbs.forms.form-grid>'))->toContain('vdbs-form-grid--2');

    $section = form('<x-vdbs.forms.form-section title="Person" description="Angaben">x</x-vdbs.forms.form-section>');
    expect($section)->toContain('aria-labelledby="section-person"')->toContain('<h2 class="vdbs-form-section__title" id="section-person">')->toContain('Angaben');

    $fieldset = form('<x-vdbs.forms.fieldset legend="Anschrift" :optional="true" hint="Wohnort">x</x-vdbs.forms.fieldset>');
    expect($fieldset)->toContain('<legend')->toContain('Anschrift')->toContain('(optional)')->toContain('Wohnort');
});

/* ---------- Login ---------- */

it('keeps the entered email and shows the error summary after a failed login', function () {
    $this->withoutVite();

    $this->followingRedirects()
        ->from('/login')
        ->post('/login', ['email' => 'erika@example.org', 'password' => 'falsch'])
        ->assertOk()
        ->assertSee('value="erika@example.org"', false)
        ->assertSee('vdbs-error-summary', false)
        ->assertSee('Die Anmeldedaten sind nicht korrekt');
});

it('renders a multi select with chips for the preselection and a checkbox list', function () {
    $html = form('<x-vdbs.forms.multi-select name="areas" label="Bereiche" :value="[\'b\']" :searchable="true" :options="[\'a\' => \'Alpha\', \'b\' => \'Beta\', \'c\' => \'Gamma\']" />');

    expect($html)
        ->toContain('data-vdbs-multiselect')
        ->toContain('<fieldset')
        ->toContain('name="areas[]"')
        ->toContain('<li class="vdbs-multiselect__chip">Beta</li>')
        ->toContain('data-vdbs-multiselect-filter')
        ->toContain('aria-controls="field-areas-panel"')
        ->toContain('vdbs-js-only');

    expect(preg_match('/id="field-areas-b"[^>]*checked/', $html))->toBe(1)
        ->and(preg_match('/id="field-areas-a"[^>]*checked/', $html))->toBe(0)
        ->and(substr_count($html, '<li class="vdbs-multiselect__chip">'))->toBe(1);
});

it('shows the placeholder and the error of a multi select', function () {
    $html = form('<x-vdbs.forms.multi-select name="x" label="X" placeholder="Wählen" error="Bitte wählen" :options="[\'a\' => \'A\']" />');

    expect($html)->toContain('Wählen')->toContain('Bitte wählen')->not->toContain('<li class="vdbs-multiselect__chip">');
});

it('renders the aside layout with image, panel and sidebar variants', function () {
    $image = form('<x-vdbs.layout.aside-layout variant="image" size="half" main-width="narrow">Inhalt<x-slot:aside>Bild</x-slot:aside></x-vdbs.layout.aside-layout>');

    expect($image)
        ->toContain('vdbs-aside-layout--image')
        ->toContain('vdbs-aside-layout--size-half')
        ->toContain('vdbs-aside-layout__content--narrow')
        ->toContain('vdbs-aside-layout--mobile-hidden')
        ->not->toContain('<aside');

    expect(preg_match('/<div class="vdbs-aside-layout__aside"\s*>/', $image))->toBe(1);

    $sidebar = form('<x-vdbs.layout.aside-layout variant="sidebar" side="start" size="s" :sticky="true" aside-label="Navigation">Inhalt<x-slot:aside>Leiste</x-slot:aside></x-vdbs.layout.aside-layout>');

    expect($sidebar)
        ->toContain('vdbs-aside-layout--side-start')
        ->toContain('vdbs-aside-layout--sticky')
        ->toContain('vdbs-aside-layout--mobile-below');

    expect(preg_match('/<aside class="vdbs-aside-layout__aside"\s+aria-label="Navigation"\s*>/', $sidebar))->toBe(1);
});

it('falls back for unknown aside layout options and omits the aside without a slot', function () {
    $html = form('<x-vdbs.layout.aside-layout variant="weird" side="oben" size="xxl" main-width="riesig">Inhalt</x-vdbs.layout.aside-layout>');

    expect($html)->toContain('vdbs-aside-layout--image')->toContain('vdbs-aside-layout--side-end')->toContain('vdbs-aside-layout--size-m')->toContain('vdbs-aside-layout__content--fill')->not->toContain('vdbs-aside-layout__aside');
});

it('shows the login without an image by default and with the configured image', function () {
    $this->withoutVite();

    $this->get('/login')->assertOk()->assertDontSee('vdbs-aside-layout', false);

    config(['portal.login_image' => ['src' => '/images/placeholder/hero-02.jpg', 'alt' => '']]);

    $this->get('/login')->assertOk()->assertSee('vdbs-aside-layout--image', false)->assertSee('Bildquelle:');
});
