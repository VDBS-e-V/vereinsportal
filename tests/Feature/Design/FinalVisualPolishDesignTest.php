<?php

it('keeps normal content cards flat and reserves shadows for depth', function () {
    $cards = file_get_contents(
        resource_path('css/vdbs/components/cards.css'),
    );

    expect($cards)
        ->toContain('box-shadow: none')
        ->not->toContain('box-shadow: var(--shadow-sm)');
});

it('ships final small screen content refinements', function () {
    $editorial = file_get_contents(
        resource_path('css/vdbs/components/editorial.css'),
    );
    $events = file_get_contents(
        resource_path('css/vdbs/components/events.css'),
    );
    $resources = file_get_contents(
        resource_path('css/vdbs/components/resources.css'),
    );
    $records = file_get_contents(
        resource_path('css/vdbs/components/records.css'),
    );
    $danger = file_get_contents(
        resource_path('css/vdbs/components/danger.css'),
    );

    expect($editorial)
        ->toContain('clamp(var(--text-3xl), 5vw, var(--text-4xl))')
        ->toContain('.editorial-accent');

    expect($events)
        ->toContain('.event-detail-meta')
        ->toContain('grid-template-columns: minmax(0, 1fr)');

    expect($resources)
        ->toContain('.resource-item__action .btn')
        ->toContain('width: 100%');

    expect($records)
        ->toContain('.key-facts dd')
        ->toContain('font-size: var(--text-lg)');

    expect($danger)
        ->toContain('.danger-zone__actions .btn')
        ->toContain('width: 100%');
});

it('documents the transition from design construction to manual qa', function () {
    $handoff = file_get_contents(
        base_path('docs/99_CURRENT_HANDOFF.md'),
    );
    $status = file_get_contents(
        base_path('docs/10_DESIGN_SYSTEM_STATUS.md'),
    );

    expect($handoff)
        ->toContain('manuelle Responsive-Abnahme')
        ->toContain('Designsystem-Baukasten');

    expect($status)
        ->toContain('Implementierung des Designsystem-Baukastens abgeschlossen, manuelle v1-QA offen')
        ->toContain('Noch durchzuführen')
        ->toContain('Fachanforderung');
});
