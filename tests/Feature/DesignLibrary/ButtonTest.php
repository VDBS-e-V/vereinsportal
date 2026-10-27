<?php

use Illuminate\Support\Facades\Blade;

function button(string $attributes = '', string $slot = 'Aktion'): string
{
    return Blade::render("<x-vdbs.actions.button {$attributes}>{$slot}</x-vdbs.actions.button>");
}

it('renders a primary solid medium button by default', function () {
    expect(button())
        ->toContain('<button')
        ->toContain('type="button"')
        ->toContain('vdbs-btn--primary')
        ->toContain('vdbs-btn--shape-solid')
        ->toContain('vdbs-btn--size-m');
});

it('uses the default shape of each role and accepts explicit shapes', function () {
    expect(button('variant="secondary"'))->toContain('vdbs-btn--shape-outline')
        ->and(button('variant="tertiary"'))->toContain('vdbs-btn--shape-link')
        ->and(button('variant="danger"'))->toContain('vdbs-btn--shape-solid')
        ->and(button('variant="quiet"'))->toContain('vdbs-btn--shape-link')
        ->and(button('shape="line"'))->toContain('vdbs-btn--shape-line');
});

it('falls back for unknown values and keeps the old link alias', function () {
    expect(button('variant="bunt" size="xxl" on="mond"'))
        ->toContain('vdbs-btn--primary')
        ->toContain('vdbs-btn--size-m')
        ->not->toContain('vdbs-btn--on-');

    expect(button('variant="link"'))->toContain('vdbs-btn--tertiary');
});

it('supports the xl size and the dark and brand surfaces', function () {
    expect(button('size="xl" on="dark"'))->toContain('vdbs-btn--size-xl')->toContain('vdbs-btn--on-dark')
        ->and(button('on="brand"'))->toContain('vdbs-btn--on-brand');
});

it('renders a link when href is set and opens external links safely', function () {
    $html = button('href="https://example.org" :external="true"');

    expect($html)
        ->toContain('<a')
        ->toContain('href="https://example.org"')
        ->toContain('target="_blank"')
        ->toContain('rel="noopener noreferrer"')
        ->toContain('öffnet in neuem Tab');
});

it('disables links without removing them from the accessibility tree', function () {
    $html = button('href="/x" :disabled="true"');

    expect($html)->toContain('aria-disabled="true"')->toContain('tabindex="-1"')->not->toContain('href="/x"');
});

it('marks loading buttons as busy and disabled', function () {
    expect(button(':loading="true"'))
        ->toContain('aria-busy="true"')
        ->toContain('disabled')
        ->toContain('vdbs-btn__spinner');
});

it('labels icon-only buttons and exposes the pressed state', function () {
    $iconOnly = Blade::render('<x-vdbs.actions.button icon="trash-2" label="Löschen" />');

    expect($iconOnly)->toContain('vdbs-btn--icon-only')->toContain('aria-label="Löschen"')->not->toContain('vdbs-btn__label');

    expect(button(':pressed="true"'))->toContain('aria-pressed="true"')
        ->and(button(':pressed="false"'))->toContain('aria-pressed="false"');
});

it('adds the arrow cell for the arrow shape', function () {
    expect(button('shape="arrow"'))->toContain('vdbs-btn__arrow');
});

it('groups buttons with an accessible label', function () {
    $html = Blade::render('<x-vdbs.actions.button-group label="Aktionen" align="end">x</x-vdbs.actions.button-group>');

    expect($html)->toContain('role="group"')->toContain('aria-label="Aktionen"')->toContain('vdbs-btn-group--end');
});

it('maps the link button variants to the button system', function () {
    expect(Blade::render('<x-vdbs.actions.link-button href="/a">A</x-vdbs.actions.link-button>'))
        ->toContain('vdbs-btn--primary')->toContain('vdbs-btn--shape-arrow')
        ->and(Blade::render('<x-vdbs.actions.link-button href="/a" variant="line">A</x-vdbs.actions.link-button>'))
        ->toContain('vdbs-btn--secondary')->toContain('vdbs-btn--shape-line')
        ->and(Blade::render('<x-vdbs.actions.link-button href="/a" variant="all">A</x-vdbs.actions.link-button>'))
        ->toContain('vdbs-btn--tertiary')->toContain('vdbs-btn--shape-link');
});
