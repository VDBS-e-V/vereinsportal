<?php

use Illuminate\Support\Facades\Blade;

it('renders the image hero with title, credit and decorative image', function () {
    $html = Blade::render('<x-vdbs.media.hero title="Willkommen" image="/x.svg" image-alt="" credit="Bild: Test" />');

    expect($html)
        ->toContain('vdbs-hero--image')
        ->toContain('<h1 class="vdbs-hero__title"')
        ->toContain('alt=""')
        ->toContain('Bild: Test');
});

it('renders the caption variant with an accessible link', function () {
    $html = Blade::render('<x-vdbs.media.hero variant="caption" title="T" image="/x.svg" image-alt="Alt" caption="Unterzeile" caption-href="/mehr" />');

    expect($html)
        ->toContain('vdbs-hero--caption')
        ->toContain('href="/mehr"')
        ->toContain('Mehr erfahren: Unterzeile');
});

it('renders the panel variant with search and aside tiles', function () {
    $html = Blade::render(<<<'BLADE'
        <x-vdbs.media.hero variant="panel" title="Suche" :level="2">
            <x-slot:search>Suchfeld</x-slot:search>
            <x-slot:aside><x-vdbs.media.hero-tile title="Schulen" href="/schulen" /></x-slot:aside>
        </x-vdbs.media.hero>
        BLADE);

    expect($html)
        ->toContain('vdbs-hero--panel')
        ->toContain('<h2 class="vdbs-hero__title"')
        ->toContain('vdbs-hero__search')
        ->toContain('vdbs-hero-tile')
        ->toContain('href="/schulen"');
});

it('falls back to the image variant for unknown variants', function () {
    expect(Blade::render('<x-vdbs.media.hero variant="unbekannt" title="T" />'))
        ->toContain('vdbs-hero--image');
});

it('renders the overlay, split and compact variants with size and bleed', function () {
    foreach (['overlay', 'split', 'compact'] as $variant) {
        $html = Blade::render('<x-vdbs.media.hero variant="'.$variant.'" size="l" :bleed="true" title="T" image="/x.svg" />');

        expect($html)
            ->toContain("vdbs-hero--{$variant}")
            ->toContain('vdbs-hero--size-l')
            ->toContain('vdbs-hero--bleed');
    }
});

it('falls back to size m for unknown sizes', function () {
    expect(Blade::render('<x-vdbs.media.hero title="T" size="xl" />'))->toContain('vdbs-hero--size-m');
});
