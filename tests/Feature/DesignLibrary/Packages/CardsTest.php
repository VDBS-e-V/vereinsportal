<?php

use Illuminate\Support\Facades\Blade;

it('renders the link card as one link with icon, text, slot and a group color', function () {
    $html = Blade::render('<x-vdbs.cards.link-card title="Sitzungen" href="/s" icon="calendar" :tone="3" text="Planung">Extra</x-vdbs.cards.link-card>');

    expect($html)->toContain('vdbs-link-card')->toContain('href="/s"')->toContain('Sitzungen')->toContain('Planung')->toContain('Extra')->toContain('--card-tone: var(--group-3)')->toContain('vdbs-link-card__icon')
        ->and(substr_count($html, '<a '))->toBe(1);

    expect(Blade::render('<x-vdbs.cards.link-card title="Nur Titel" href="/x" :tone="9" />'))->not->toContain('vdbs-link-card__icon')->not->toContain('vdbs-link-card__text')->toContain('var(--group-6)');
});
