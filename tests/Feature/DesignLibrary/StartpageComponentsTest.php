<?php

use Illuminate\Support\Facades\Blade;

it('renders a section with a labelled heading and actions', function () {
    $html = Blade::render('<x-vdbs.layout.section title="Test Titel" lead="Lead" variant="band" align="center">Inhalt<x-slot:actions>Aktion</x-slot:actions></x-vdbs.layout.section>');

    expect($html)
        ->toContain('vdbs-section--band')
        ->toContain('vdbs-section--align-center')
        ->toContain('aria-labelledby="section-test-titel-title"')
        ->toContain('<h2 class="vdbs-section__title"')
        ->toContain('vdbs-section__actions');
});

it('renders a split with media and the reverse modifier', function () {
    $html = Blade::render('<x-vdbs.layout.split title="Zugang" :reverse="true">Text<x-slot:media>Bild</x-slot:media></x-vdbs.layout.split>');

    expect($html)->toContain('vdbs-split--reverse')->toContain('vdbs-split__media');
});

it('limits grid columns to the supported values', function () {
    expect(Blade::render('<x-vdbs.layout.grid :columns="7">x</x-vdbs.layout.grid>'))->toContain('vdbs-grid--3');
});

it('renders quick links with the group tone as accent class', function () {
    $html = Blade::render('<x-vdbs.cards.quick-links><x-vdbs.cards.quick-link label="Hilfe" href="/hilfe" :tone="9" /></x-vdbs.cards.quick-links>');

    expect($html)->toContain('vdbs-quick-link--tone-6')->toContain('href="/hilfe"')->toContain('aria-label="Schnellzugriff"');
});

it('renders a teaser with one link on the title only', function () {
    $html = Blade::render('<x-vdbs.cards.teaser title="Artikel" href="/a" text="Text" category="Thema" image="/x.svg" credit="Bild: X" />');

    expect($html)
        ->toContain('<h3 class="vdbs-teaser__title">')
        ->toContain('Thema')
        ->toContain('Bild: X')
        ->and(substr_count($html, '<a '))->toBe(1);
});

it('renders contact lines as a description list', function () {
    $html = Blade::render('<x-vdbs.cards.contact-block :lines="[[\'label\' => \'Support\', \'value\' => \'a@b.de\', \'href\' => \'mailto:a@b.de\']]" />');

    expect($html)->toContain('<dl class="vdbs-contact-block__lines">')->toContain('mailto:a@b.de');
});

it('renders chips, figure, link buttons and back to top', function () {
    $html = Blade::render(<<<'BLADE'
        <x-vdbs.data.chip-list label="Bereiche"><x-vdbs.data.chip label="Schulen" href="/s" :mark="4" icon="school" /></x-vdbs.data.chip-list>
        <x-vdbs.media.figure src="/x.svg" alt="" caption="Unterschrift" credit="Bild: X" />
        <x-vdbs.actions.link-button href="https://x.org" variant="line" :external="true">Extern</x-vdbs.actions.link-button>
        <x-vdbs.actions.back-to-top />
        BLADE);

    expect($html)
        ->toContain('aria-label="Bereiche"')
        ->toContain('vdbs-chip__label')
        ->toContain('vdbs-image-credit--below')
        ->toContain('vdbs-btn--shape-line')
        ->toContain('rel="noopener noreferrer"')
        ->toContain('öffnet in neuem Tab')
        ->toContain('href="#top"');
});

it('renders a feature block with variant, tone and one image source', function () {
    $html = Blade::render('<x-vdbs.content.feature variant="offset" tone="dark" eyebrow="Kicker" title="Titel" image="/images/placeholder/split-01.jpg" image-alt="">Text<x-slot:actions>Aktion</x-slot:actions></x-vdbs.content.feature>');

    expect($html)
        ->toContain('vdbs-feature--offset')
        ->toContain('vdbs-feature--tone-dark')
        ->toContain('vdbs-on-dark')
        ->toContain('aria-labelledby="feature-titel-title"')
        ->toContain('vdbs-feature__eyebrow')
        ->toContain('vdbs-feature__media');
});

it('renders the links feature without media and with a stacked actions column', function () {
    $html = Blade::render('<x-vdbs.content.feature variant="links" tone="surface" title="Haltung" image="/x.jpg">Text<x-slot:actions>A</x-slot:actions></x-vdbs.content.feature>');

    expect($html)->toContain('vdbs-feature--no-media')->toContain('vdbs-feature__actions--stack')->not->toContain('vdbs-figure');
});

it('falls back to the side variant and no tone for unknown values', function () {
    $html = Blade::render('<x-vdbs.content.feature variant="x" tone="y" title="T">Text</x-vdbs.content.feature>');

    expect($html)->toContain('vdbs-feature--side')->toContain('vdbs-feature--tone-none');
});

it('renders teaser variants with one link and the tone class', function () {
    $html = Blade::render('<x-vdbs.cards.teaser variant="compact" tone="white" title="Ort" meta="Adresse 1" href="/o" category="Standort" image="/x.svg" credit="Bild: X" />');

    expect($html)
        ->toContain('vdbs-teaser--compact')
        ->toContain('vdbs-teaser--white')
        ->toContain('vdbs-teaser__meta')
        ->and(substr_count($html, '<a '))->toBe(1);
});

it('falls back to the default teaser variant', function () {
    expect(Blade::render('<x-vdbs.cards.teaser variant="x" title="T" href="/t" />'))->toContain('vdbs-teaser--default');
});

it('supports start alignment in the grid', function () {
    expect(Blade::render('<x-vdbs.layout.grid align="start">x</x-vdbs.layout.grid>'))->toContain('vdbs-grid--align-start');
});

it('renders an event list with items, time and footer', function () {
    $html = Blade::render('<x-vdbs.data.event-list href="/t"><x-vdbs.data.event-item date="01.11." datetime="2026-11-01" time="13:00" title="Vortrag" href="/v" /><x-slot:controls>1 / 3</x-slot:controls></x-vdbs.data.event-list>');

    expect($html)
        ->toContain('aria-labelledby="events-veranstaltungskalender-title"')
        ->toContain('<time datetime="2026-11-01">01.11.</time>')
        ->toContain('13:00')
        ->toContain('vdbs-event-list__controls')
        ->toContain('Alle Termine');
});

it('renders link tabs with the current page marked', function () {
    $html = Blade::render('<x-vdbs.navigation.tab-nav label="Konto" :items="[[\'label\' => \'Profil\', \'href\' => \'/p\', \'current\' => true], [\'label\' => \'Sicherheit\', \'href\' => \'/s\', \'badge\' => \'2\']]" />');

    expect($html)
        ->toContain('aria-label="Konto"')
        ->toContain('aria-current="page"')
        ->toContain('vdbs-tab-nav__badge')
        ->and(substr_count($html, 'aria-current'))->toBe(1);
});

it('renders content tabs as sections for the script to enhance', function () {
    $html = Blade::render('<x-vdbs.navigation.tabs label="Test" active="b"><x-vdbs.navigation.tab id="a" label="Eins">A</x-vdbs.navigation.tab><x-vdbs.navigation.tab id="b" label="Zwei">B</x-vdbs.navigation.tab></x-vdbs.navigation.tabs>');

    expect($html)
        ->toContain('data-vdbs-tabs')
        ->toContain('data-active="b"')
        ->toContain('data-label="Eins"')
        ->toContain('<h3 class="vdbs-tab__title">Zwei</h3>');
});

it('renders pagination with gaps, the current page and disabled edges', function () {
    $html = Blade::render('<x-vdbs.navigation.pagination :current="1" :last="20" href="/l?page=:page" />');

    expect($html)
        ->toContain('Seite 1 von 20')
        ->toContain('aria-current="page"')
        ->toContain('href="/l?page=20"')
        ->toContain('…')
        ->toContain('vdbs-pagination__link--disabled')
        ->not->toContain('href="/l?page=10"');
});

it('renders no pagination for a single page', function () {
    expect(trim(Blade::render('<x-vdbs.navigation.pagination :current="1" :last="1" />')))->toBe('');
});

it('uses a laravel paginator for the pagination', function () {
    $paginator = new Illuminate\Pagination\LengthAwarePaginator(range(1, 10), 50, 10, 3, ['path' => '/nutzer']);
    $html = Blade::render('<x-vdbs.navigation.pagination :paginator="$p" />', ['p' => $paginator]);

    expect($html)->toContain('Seite 3 von 5')->toContain('/nutzer?page=2')->toContain('rel="next"');
});

it('renders the stepper with done, current and open steps', function () {
    $html = Blade::render('<x-vdbs.navigation.stepper :current="2" :steps="[\'A\', [\'label\' => \'B\', \'hint\' => \'Hinweis\'], \'C\']" />');

    expect($html)
        ->toContain('Schritt 2 von 3')
        ->toContain('vdbs-stepper__step--done')
        ->toContain('vdbs-stepper__step--todo')
        ->toContain('aria-current="step"')
        ->toContain('(erledigt)')
        ->toContain('Hinweis');
});

it('renders a dropdown on details with links and buttons', function () {
    $html = Blade::render('<x-vdbs.navigation.dropdown label="Menü" align="end"><x-vdbs.navigation.dropdown-item href="/a" icon="user">Konto</x-vdbs.navigation.dropdown-item><x-vdbs.navigation.dropdown-item type="submit">Abmelden</x-vdbs.navigation.dropdown-item></x-vdbs.navigation.dropdown>');

    expect($html)
        ->toContain('<details')
        ->toContain('vdbs-dropdown--end')
        ->toContain('data-vdbs-dropdown')
        ->toContain('href="/a"')
        ->toContain('type="submit"');
});

it('builds avatar initials and prefers a photo', function () {
    expect(Blade::render('<x-vdbs.data.avatar name="erika Mustermann" />'))->toContain('>EM<');
    expect(Blade::render('<x-vdbs.data.avatar name="Max" />'))->toContain('>M<');
    expect(Blade::render('<x-vdbs.data.avatar name="Max" src="/a.jpg" />'))->toContain('vdbs-avatar__img')->not->toContain('vdbs-avatar__initials');
});

it('renders the side navigation with sections and a flat list', function () {
    $html = Blade::render('<x-vdbs.navigation.side-nav label="Konto" :sections="[[\'title\' => \'Konto\', \'items\' => [[\'label\' => \'Profil\', \'href\' => \'/p\', \'current\' => true]]]]" />');
    $flat = Blade::render('<x-vdbs.navigation.side-nav label="Flach" :sections="[[\'label\' => \'A\', \'href\' => \'/a\']]" />');

    expect($html)->toContain('vdbs-side-nav__title')->toContain('aria-current="page"');
    expect($flat)->toContain('href="/a"')->not->toContain('vdbs-side-nav__title');
});

it('shows the user menu with the logout in the header for logged in users', function () {
    $this->actingAs(App\Modules\Access\Models\User::factory()->create(['email' => 'erika@example.org']))
        ->get('/')
        ->assertOk()
        ->assertSee('vdbs-dropdown', false)
        ->assertSee('erika@example.org')
        ->assertSee('Abmelden');
});

it('renders toasts with the right live region role and timeout defaults', function () {
    $success = Blade::render('<x-vdbs.feedback.toast variant="success" title="Fertig">Gespeichert</x-vdbs.feedback.toast>');
    $error = Blade::render('<x-vdbs.feedback.toast variant="error">Fehler</x-vdbs.feedback.toast>');

    expect($success)->toContain('role="status"')->toContain('data-timeout="8"')->toContain('vdbs-toast__title')->toContain('data-vdbs-toast-close');
    expect($error)->toContain('role="alert"')->not->toContain('data-timeout');
});

it('shows session flash messages as toasts and nothing without messages', function () {
    expect(trim(Blade::render('<x-vdbs.feedback.flash />')))->toBe('');

    session()->flash('status', 'Profil gespeichert.');
    session()->flash('error', 'Das ging schief.');
    $html = Blade::render('<x-vdbs.feedback.flash />');

    expect($html)->toContain('data-vdbs-toast-region')->toContain('Profil gespeichert.')->toContain('Das ging schief.')->toContain('vdbs-toast--success')->toContain('vdbs-toast--error');
});

it('renders an empty state with icon, text and actions', function () {
    $html = Blade::render('<x-vdbs.feedback.empty-state title="Keine Treffer" icon="search" size="s"><p>Text</p><x-slot:actions>Aktion</x-slot:actions></x-vdbs.feedback.empty-state>');

    expect($html)->toContain('vdbs-empty--s')->toContain('<h3 class="vdbs-empty__title">Keine Treffer</h3>')->toContain('vdbs-empty__actions');
});

it('renders skeletons as decoration or as a labelled status', function () {
    expect(Blade::render('<x-vdbs.feedback.skeleton :lines="3" />'))->toContain('aria-hidden="true"')->toContain('data-last')->not->toContain('role="status"');
    expect(Blade::render('<x-vdbs.feedback.skeleton variant="card" label="Lädt …" />'))->toContain('role="status"')->toContain('Lädt …');
    expect(Blade::render('<x-vdbs.feedback.skeleton variant="x" />'))->toContain('vdbs-skeleton--text');
});

it('renders determinate and indeterminate progress bars', function () {
    $determinate = Blade::render('<x-vdbs.feedback.progress label="Upload" :value="64" />');
    $indeterminate = Blade::render('<x-vdbs.feedback.progress label="Import" />');

    expect($determinate)->toContain('value="64"')->toContain('64 %')->toContain('for="progress-upload"');
    expect($indeterminate)->not->toContain('value=')->toContain('läuft');
});

it('renders a tooltip with a role and the chosen placement', function () {
    $html = Blade::render('<x-vdbs.feedback.tooltip text="Hilfe" placement="bottom"><button>?</button></x-vdbs.feedback.tooltip>');

    expect($html)->toContain('vdbs-tooltip--bottom')->toContain('role="tooltip"')->toContain('data-vdbs-tooltip')->toContain('Hilfe');
});

it('renders the toolbar as a search form with filters, tags and count', function () {
    $html = Blade::render('<x-vdbs.data.toolbar label="Nutzer filtern" count="3 Nutzer" reset-href="/n" :active="[[\'label\' => \'Rolle: Vorstand\', \'href\' => \'/n?x=1\']]"><span>Filterfeld</span><x-slot:actions>Anlegen</x-slot:actions></x-vdbs.data.toolbar>');

    expect($html)
        ->toContain('role="search"')
        ->toContain('method="get"')
        ->toContain('aria-label="Nutzer filtern"')
        ->toContain('name="q"')
        ->toContain('vdbs-toolbar__filters')
        ->toContain('vdbs-toolbar__actions')
        ->toContain('3 Nutzer')
        ->toContain('aria-label="Filter entfernen: Rolle: Vorstand"')
        ->toContain('Zurücksetzen');
});

it('shows the reset link and the meta row only with active filters or a count', function () {
    $html = Blade::render('<x-vdbs.data.toolbar reset-href="/n" />');

    expect($html)->not->toContain('Zurücksetzen')->not->toContain('vdbs-toolbar__meta');
});

it('renders a detail list with links and a dash for empty values', function () {
    $html = Blade::render('<x-vdbs.data.detail-list :columns="2" layout="inline"><x-vdbs.data.detail-item label="E-Mail" href="mailto:a@b.de">a@b.de</x-vdbs.data.detail-item><x-vdbs.data.detail-item label="Telefon"></x-vdbs.data.detail-item></x-vdbs.data.detail-list>');

    expect($html)
        ->toContain('<dl')
        ->toContain('vdbs-detail-list--2')
        ->toContain('vdbs-detail-list--inline')
        ->toContain('href="mailto:a@b.de"')
        ->toContain('nicht angegeben');
});

it('renders a stat with the trend as text, tone and optional link', function () {
    $html = Blade::render('<x-vdbs.data.stat label="Aktive Nutzer" value="128" tone="success" trend="up" trend-label="+12 seit letztem Monat" href="/n" hint="Stand heute" />');

    expect($html)
        ->toContain('vdbs-stat--success')
        ->toContain('vdbs-stat--link')
        ->toContain('<dd class="vdbs-stat__value">128</dd>')
        ->toContain('gestiegen:')
        ->toContain('+12 seit letztem Monat')
        ->toContain('Stand heute')
        ->toContain('href="/n"');
    expect(Blade::render('<x-vdbs.data.stat label="X" value="1" tone="x" trend="y" trend-label="z" />'))->toContain('vdbs-stat--neutral')->not->toContain('vdbs-stat__trend');
});

it('renders a timeline with time, actor, description and tone', function () {
    $html = Blade::render('<x-vdbs.data.timeline label="Aktivitäten"><x-vdbs.data.timeline-item title="Passwort geändert" time="28.10.2026" datetime="2026-10-28" actor="Erika" tone="success">Details</x-vdbs.data.timeline-item></x-vdbs.data.timeline>');

    expect($html)
        ->toContain('<ol')
        ->toContain('aria-label="Aktivitäten"')
        ->toContain('vdbs-timeline-item--success')
        ->toContain('<time datetime="2026-10-28">28.10.2026</time>')
        ->toContain('Erika')
        ->toContain('Details');
});

it('renders the page header with breadcrumb, meta, actions and tabs', function () {
    $html = Blade::render('<x-vdbs.layout.page-header title="Erika" eyebrow="Nutzer" lead="Lead" :breadcrumb="[[\'label\' => \'Start\', \'href\' => \'/\'], [\'label\' => \'Erika\']]"><x-slot:meta>Aktiv</x-slot:meta><x-slot:actions>Bearbeiten</x-slot:actions><x-slot:tabs>Tabs</x-slot:tabs></x-vdbs.layout.page-header>');

    expect($html)
        ->toContain('<h1 class="vdbs-page-header__title" id="page-erika-title">Erika</h1>')
        ->toContain('aria-labelledby="page-erika-title"')
        ->toContain('vdbs-breadcrumb')
        ->toContain('vdbs-page-header__meta')
        ->toContain('vdbs-page-header__actions')
        ->toContain('vdbs-page-header__tabs');
});

it('renders a confirm dialog with form, csrf, method spoofing and safe default focus', function () {
    $html = Blade::render('<x-vdbs.overlays.confirm-dialog id="del" title="Löschen?" variant="danger" action="/n/1" method="delete" phrase="LÖSCHEN">Folgen</x-vdbs.overlays.confirm-dialog>');

    expect($html)
        ->toContain('<dialog id="del"')
        ->toContain('vdbs-modal--danger')
        ->toContain('aria-describedby="del-text"')
        ->toContain('action="/n/1"')
        ->toContain('name="_token"')
        ->toContain('name="_method" value="DELETE"')
        ->toContain('data-vdbs-confirm-phrase="LÖSCHEN"')
        ->toContain('Tippe „LÖSCHEN“ zur Bestätigung')
        ->toContain('autofocus')
        ->toContain('vdbs-btn--danger');
});

it('renders a plain confirm dialog and lets the caller supply the confirm button', function () {
    $html = Blade::render('<x-vdbs.overlays.confirm-dialog id="x" title="Senden?">Text<x-slot:submit><button type="button" id="own">Eigener</button></x-slot:submit></x-vdbs.overlays.confirm-dialog>');

    expect($html)->toContain('id="own"')->not->toContain('data-vdbs-confirm-submit')->not->toContain('data-vdbs-confirm-phrase');
});

it('renders a drawer with side, size and a footer', function () {
    $html = Blade::render('<x-vdbs.overlays.drawer id="f" title="Filter" side="start" size="s">Inhalt<x-slot:actions>Anwenden</x-slot:actions></x-vdbs.overlays.drawer>');

    expect($html)->toContain('vdbs-drawer--start')->toContain('vdbs-drawer--s')->toContain('data-vdbs-dialog')->toContain('vdbs-drawer__actions');
    expect(Blade::render('<x-vdbs.overlays.drawer id="g" title="G" side="x" size="y">I</x-vdbs.overlays.drawer>'))->toContain('vdbs-drawer--end')->toContain('vdbs-drawer--m');
});

it('renders accordion items as details and groups exclusive ones by name', function () {
    $open = Blade::render('<x-vdbs.disclosure.accordion><x-vdbs.disclosure.accordion-item title="Frage" :open="true">Antwort</x-vdbs.disclosure.accordion-item></x-vdbs.disclosure.accordion>');
    $exclusive = Blade::render('<x-vdbs.disclosure.accordion exclusive="faq"><x-vdbs.disclosure.accordion-item title="A">a</x-vdbs.disclosure.accordion-item><x-vdbs.disclosure.accordion-item title="B">b</x-vdbs.disclosure.accordion-item></x-vdbs.disclosure.accordion>');

    expect($open)->toContain('<details')->toContain(' open')->toContain('<h3 class="vdbs-accordion__title">Frage</h3>')->not->toContain('name=');
    expect(substr_count($exclusive, 'name="faq"'))->toBe(2);
});

it('renders prose, a quote with source and a brand tone', function () {
    expect(Blade::render('<x-vdbs.content.prose size="l"><p>Text</p></x-vdbs.content.prose>'))->toContain('vdbs-prose--l');

    $quote = Blade::render('<x-vdbs.content.quote tone="brand" source="Erika" role="Schule" cite="/q">Aussage</x-vdbs.content.quote>');
    expect($quote)->toContain('<blockquote')->toContain('cite="/q"')->toContain('vdbs-quote--brand')->toContain('Erika')->toContain('Schule');
});

it('renders a download card with one link and a readable file description', function () {
    $html = Blade::render('<x-vdbs.content.download-card title="Satzung" href="/s.pdf" type="PDF" size="240 KB" text="Fassung 2024" />');

    expect($html)
        ->toContain('href="/s.pdf"')
        ->toContain('download')
        ->toContain('Download, PDF, 240 KB')
        ->toContain('Fassung 2024')
        ->and(substr_count($html, '<a '))->toBe(1);
});

it('renders an event card with a machine readable date and one link', function () {
    $html = Blade::render('<x-vdbs.content.event-card title="Versammlung" href="/t" date="2026-11-14" time="10:00 Uhr" location="Berlin" />');

    expect($html)
        ->toContain('<time class="vdbs-event-card__date" datetime="2026-11-14">')
        ->toContain('>14<')
        ->toContain('Samstag, 14. November 2026')
        ->toContain('10:00 Uhr')
        ->toContain('Berlin')
        ->and(substr_count($html, '<a '))->toBe(1);
});

it('renders a list of one-time codes', function () {
    $html = Blade::render('<x-vdbs.data.code-list :codes="[\'ABCDE-FGHJK\', \'23456-789AB\']" label="Meine Codes" />');

    expect($html)->toContain('aria-label="Meine Codes"')->toContain('<code>ABCDE-FGHJK</code>')->and(substr_count($html, 'vdbs-code-list__item'))->toBe(2);
});
