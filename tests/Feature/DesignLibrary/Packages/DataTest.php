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
