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
        ->toContain('vdbs-figure__credit')
        ->toContain('vdbs-link-button--line')
        ->toContain('rel="noopener noreferrer"')
        ->toContain('öffnet in neuem Tab')
        ->toContain('href="#top"');
});
