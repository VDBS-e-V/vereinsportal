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
    expect(button('variant="primary"'))->toContain('vdbs-btn--shape-solid')
        ->and(button('variant="secondary"'))->toContain('vdbs-btn--shape-solid')
        ->and(button('variant="tertiary"'))->toContain('vdbs-btn--shape-outline')
        ->and(button('variant="text"'))->toContain('vdbs-btn--text')->toContain('vdbs-btn--shape-text')
        ->and(button('variant="danger"'))->toContain('vdbs-btn--shape-solid')
        ->and(button('shape="line"'))->toContain('vdbs-btn--shape-line');
});

it('keeps quiet as the old name of the text role', function () {
    expect(button('variant="quiet"'))->toContain('vdbs-btn--text')->toContain('vdbs-btn--shape-text')->not->toContain('vdbs-btn--quiet');
});

it('offers the tones green and lila with green as default', function () {
    expect(button())->not->toContain('vdbs-btn--tone-lila')
        ->and(button('tone="green"'))->not->toContain('vdbs-btn--tone-lila')
        ->and(button('tone="lila"'))->toContain('vdbs-btn--tone-lila')
        ->and(button('tone="pink"'))->not->toContain('vdbs-btn--tone-lila');

    foreach (['primary', 'secondary', 'tertiary', 'text'] as $variant) {
        expect(button('variant="'.$variant.'" tone="lila"'))->toContain('vdbs-btn--'.$variant)->toContain('vdbs-btn--tone-lila');
    }
});

it('works with and without icon and text in every role and tone', function () {
    foreach (['primary', 'secondary', 'tertiary', 'text', 'danger'] as $variant) {
        foreach (['green', 'lila'] as $tone) {
            $base = 'variant="'.$variant.'" tone="'.$tone.'"';

            $textOnly = button($base);
            $iconStart = button($base.' icon="plus"');
            $iconEnd = button($base.' icon="arrow-right" icon-position="end"');
            $iconOnly = Blade::render('<x-vdbs.actions.button '.$base.' icon="pencil" label="Bearbeiten" />');

            expect($textOnly)->toContain('vdbs-btn__label')->not->toContain('<svg')
                ->and($iconStart)->toContain('vdbs-btn__label')->toContain('<svg')
                ->and(strpos($iconStart, '<svg'))->toBeLessThan(strpos($iconStart, 'vdbs-btn__label'))
                ->and($iconEnd)->toContain('<svg')
                ->and(strpos($iconEnd, '<svg'))->toBeGreaterThan(strpos($iconEnd, 'vdbs-btn__label'))
                ->and($iconOnly)->toContain('vdbs-btn--icon-only')->toContain('aria-label="Bearbeiten"')->toContain('title="Bearbeiten"')->toContain('<svg')->not->toContain('vdbs-btn__label');
        }
    }
});

it('keeps the states in every role and tone', function () {
    foreach (['primary', 'secondary', 'tertiary', 'text'] as $variant) {
        foreach (['green', 'lila'] as $tone) {
            $base = 'variant="'.$variant.'" tone="'.$tone.'"';

            expect(button($base.' :disabled="true"'))->toContain('vdbs-btn--disabled')->toContain('disabled')
                ->and(button($base.' :loading="true"'))->toContain('vdbs-btn--loading')->toContain('aria-busy="true"')
                ->and(button($base.' :pressed="true"'))->toContain('aria-pressed="true"');
        }
    }
});

it('defines the button tone colors for both themes in the tokens', function () {
    $tokens = file_get_contents(resource_path('css/vdbs/tokens.css'));

    foreach (['green', 'lila'] as $tone) {
        foreach (['solid', 'solid-hover', 'solid-press', 'soft', 'soft-hover', 'soft-press', 'text', 'text-hover'] as $role) {
            expect(substr_count($tokens, "--btn-{$tone}-{$role}:"))->toBe(3, "--btn-{$tone}-{$role} (hell und zweimal dunkel)");
        }
    }

    foreach (['on-solid', 'on-soft', 'disabled-bg', 'disabled-fg'] as $name) {
        expect(substr_count($tokens, "--btn-{$name}:"))->toBe(3);
    }
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
        ->and(button('on="brand"'))->toContain('vdbs-btn--on-brand')
        ->and(button('on="night" tone="lila"'))->toContain('vdbs-btn--on-night')->toContain('vdbs-btn--tone-lila');
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
    foreach (['solid', 'outline', 'text', 'link', 'arrow', 'line'] as $shape) {
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
