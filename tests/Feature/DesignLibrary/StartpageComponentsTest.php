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
