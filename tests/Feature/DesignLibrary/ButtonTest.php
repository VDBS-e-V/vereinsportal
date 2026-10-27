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

it('falls back for unknown values', function () {
    expect(button('variant="bunt" size="xxl" on="mond"'))
        ->toContain('vdbs-btn--primary')
        ->toContain('vdbs-btn--size-m')
        ->not->toContain('vdbs-btn--on-');

    expect(button('variant="link"'))->toContain('vdbs-btn--primary');
    expect(button('on="light"'))->not->toContain('vdbs-btn--on-');
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

it('adds the arrow cell for the arrow shape and keeps an optional leading icon', function () {
    expect(button('shape="arrow"'))->toContain('vdbs-btn__arrow')
        ->and(button('shape="arrow" icon="plus"'))->toContain('vdbs-btn__arrow')->toContain('<svg')
        ->and(substr_count(button('shape="arrow" icon="plus"'), '<svg'))->toBe(2);
});

it('allows an icon before or after the text in every shape', function () {
    foreach (['solid', 'outline', 'link', 'arrow', 'line'] as $shape) {
        expect(button('shape="'.$shape.'" icon="plus"'))->toContain('<svg')
            ->and(button('shape="'.$shape.'" icon="download" icon-position="end"'))->toContain('<svg');
    }
});

it('shows a chevron in the line shape by default', function () {
    expect(button('shape="line"'))->toContain('vdbs-btn__icon--chevron');
});

it('ships the lucide icons the design system uses', function () {
    foreach (['arrow-right', 'chevron-right', 'external-link', 'eye', 'trash-2', 'plus', 'download', 'pencil'] as $icon) {
        expect(Blade::render('<x-vdbs.icon name="'.$icon.'" />'))->toContain('<path');
    }
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

it('marks only disabled buttons as disabled, loading buttons keep their colors', function () {
    expect(button(':disabled="true"'))->toContain('vdbs-btn--disabled')
        ->and(button(':loading="true"'))->not->toContain('vdbs-btn--disabled')->toContain('vdbs-btn--loading');
});
