<?php

use Illuminate\Support\Facades\Blade;

it('renders the data kanban board with its token and role', function () {
    $html = Blade::render('<x-vdbs.data.kanban label="Meine Tafel" token="abc">Inhalt</x-vdbs.data.kanban>');

    expect($html)->toContain('vdbs-kanban')->toContain('data-vdbs-kanban')->toContain('data-token="abc"')->toContain('aria-label="Meine Tafel"')->toContain('Inhalt');
});

it('renders the data kanban column with title, count, status and footer', function () {
    $html = Blade::render('<x-vdbs.data.kanban-column title="Offen" :status-id="7" :count="3" :done="true">Karten<x-slot:footer>Fuß</x-slot:footer></x-vdbs.data.kanban-column>');

    expect($html)->toContain('vdbs-kanban-column')->toContain('data-status-id="7"')->toContain('Offen')->toContain('(erledigt)')->toContain('>3<')->toContain('Karten')->toContain('Fuß')->toContain('data-vdbs-kanban-list');
    expect(Blade::render('<x-vdbs.data.kanban-column title="Leer" />'))->not->toContain('data-status-id')->not->toContain('(erledigt)')->not->toContain('vdbs-kanban-column__footer');
});

it('renders the data kanban card as link, draggable only with a move url, and falls back for unknown priorities', function () {
    $html = Blade::render('<x-vdbs.data.kanban-card title="Aufgabe" href="/a" move-url="/m" priority="high">Info<x-slot:footer>Fuß</x-slot:footer></x-vdbs.data.kanban-card>');

    expect($html)->toContain('vdbs-kanban-card--high')->toContain('href="/a"')->toContain('data-move-url="/m"')->toContain('draggable="true"')->toContain('Info')->toContain('Fuß');

    $plain = Blade::render('<x-vdbs.data.kanban-card title="Nur Text" priority="dringend" />');
    expect($plain)->toContain('vdbs-kanban-card--normal')->not->toContain('draggable')->not->toContain('<a ')->toContain('Nur Text');
});

it('renders the table check as a cell with the own selection box of the design system', function () {
    $html = \Illuminate\Support\Facades\Blade::render('<x-vdbs.data.table-check name="ids[]" value="7" label="Eintrag auswählen" form="bulk" :checked="true" />');

    expect($html)->toContain('<td class="vdbs-table__select">')->toContain('vdbs-choice vdbs-choice--checkbox')->toContain('class="vdbs-choice__input" type="checkbox" name="ids[]" value="7"')
        ->toContain('aria-label="Eintrag auswählen"')->toContain('form="bulk"')->toContain('checked');

    expect(\Illuminate\Support\Facades\Blade::render('<x-vdbs.data.table-check name="x" label="Schalter" />'))->not->toContain('form=')->not->toContain('checked');
});

it('renders the link card as one link with icon, text, slot and a group color', function () {
    $html = Blade::render('<x-vdbs.cards.link-card title="Sitzungen" href="/s" icon="calendar" :tone="3" text="Planung">Extra</x-vdbs.cards.link-card>');

    expect($html)->toContain('vdbs-link-card')->toContain('href="/s"')->toContain('Sitzungen')->toContain('Planung')->toContain('Extra')->toContain('--card-tone: var(--group-3)')->toContain('vdbs-link-card__icon')
        ->and(substr_count($html, '<a '))->toBe(1);

    expect(Blade::render('<x-vdbs.cards.link-card title="Nur Titel" href="/x" :tone="9" />'))->not->toContain('vdbs-link-card__icon')->not->toContain('vdbs-link-card__text')->toContain('var(--group-6)');
});

it('renders the area hero with the color, icon, title and optional parts of the area', function () {
    $html = Blade::render('<x-vdbs.layout.area-hero :area="$area" title="Übersicht" greeting="Guten Tag, Erika" lead="Einleitung"><x-slot:actions>Aktion</x-slot:actions><x-slot:aside>Seitenkarte</x-slot:aside></x-vdbs.layout.area-hero>', ['area' => \App\Support\PortalAreas::Vorstand]);

    expect($html)->toContain('vdbs-area-hero')->toContain('--area-tone: var(--group-1)')->toContain('Bereich Vorstand')->toContain('<h1')->toContain('Übersicht')->toContain('Guten Tag, Erika')->toContain('Einleitung')
        ->toContain('Aktion')->toContain('vdbs-area-hero__aside')->toContain('Seitenkarte');

    $plain = Blade::render('<x-vdbs.layout.area-hero :area="$area" title="Konto" />', ['area' => \App\Support\PortalAreas::Konto]);

    expect($plain)->not->toContain('--area-tone: var(--group')->not->toContain('vdbs-area-hero__aside')->not->toContain('vdbs-area-hero__greeting');
});
