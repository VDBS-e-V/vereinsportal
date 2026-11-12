<?php

/**
 * Dunkelmodus (neutrales Graphit, Lila und Grün nur als Akzent): Werte und Kontraste der Tokens (docs/60_BUTTONS_KRITERIEN.md).
 */
function dmTokens(): string
{
    return (string) file_get_contents(resource_path('css/vdbs/tokens.css'));
}

/** Löst ein Token des Dunkelmodus (data-theme-Block) bis zur Primitive auf und gibt den Hex-Wert zurück. */
function dmHex(string $name): string
{
    $css = dmTokens();
    preg_match('/:root\[data-theme="dark"\]\s*\{([^}]*)\}/', $css, $dark);

    $find = function (string $block, string $token) {
        return preg_match('/'.preg_quote($token, '/').':\s*([^;]+);/', $block, $m) ? trim($m[1]) : null;
    };

    $value = $find($dark[1], $name) ?? $find($css, $name);

    while ($value !== null && preg_match('/^var\((--[a-z0-9-]+)\)/', $value, $ref)) {
        $value = $find($dark[1], $ref[1]) ?? $find($css, $ref[1]);
    }

    return strtolower(preg_replace('/\s*\/\*.*$/', '', (string) $value));
}

function dmLuminance(string $hex): float
{
    $hex = ltrim($hex, '#');
    $channel = fn (int $i): float => (function (float $c): float {
        return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
    })(hexdec(substr($hex, $i, 2)) / 255);

    return 0.2126 * $channel(0) + 0.7152 * $channel(2) + 0.0722 * $channel(4);
}

function dmContrast(string $a, string $b): float
{
    [$light, $dark] = [max(dmLuminance($a), dmLuminance($b)), min(dmLuminance($a), dmLuminance($b))];

    return ($light + 0.05) / ($dark + 0.05);
}

it('uses neutral graphite surfaces in dark mode without any purple tint', function () {
    expect(dmHex('--bg-page'))->toBe('#121416')
        ->and(dmHex('--bg-surface'))->toBe('#1b1e21')
        ->and(dmHex('--bg-accent-soft'))->toBe('#25292d')
        ->and(dmHex('--bg-header-footer'))->toBe('#1b1e21')
        ->and(dmHex('--border-input'))->toBe('#80888e')
        ->and(dmHex('--text-muted'))->toBe('#9aa1a6');

    expect(dmTokens())->not->toContain('aubergine')->not->toContain('mauve');
});

it('defines the dark button colors exactly as designed', function () {
    expect(dmHex('--btn-lila-solid'))->toBe('#a355b2')
        ->and(dmHex('--btn-lila-solid-hover'))->toBe('#7f3f8c')
        ->and(dmHex('--btn-lila-solid-press'))->toBe('#5c2e65')
        ->and(dmHex('--btn-lila-text'))->toBe('#c379d2')
        ->and(dmHex('--btn-green-solid'))->toBe('#1f8362')
        ->and(dmHex('--btn-green-solid-hover'))->toBe('#18644b')
        ->and(dmHex('--btn-green-solid-press'))->toBe('#114937')
        ->and(dmHex('--btn-green-text'))->toBe('#2aa87e')
        ->and(dmHex('--btn-on-solid'))->toBe('#ffffff')
        ->and(dmHex('--btn-disabled-bg'))->toBe('#2d3135');
});

it('keeps the contrast of the dark buttons above the thresholds', function () {
    $surfaces = ['--bg-page', '--bg-surface', '--bg-accent-soft'];

    foreach (['green', 'lila'] as $tone) {
        foreach (['solid', 'solid-hover', 'solid-press'] as $state) {
            expect(dmContrast(dmHex('--btn-on-solid'), dmHex("--btn-{$tone}-{$state}")))->toBeGreaterThanOrEqual(4.5, "{$tone} {$state}: weiße Schrift");
        }

        expect(dmContrast(dmHex("--btn-{$tone}-solid"), dmHex('--bg-page')))->toBeGreaterThanOrEqual(3.0, "{$tone}: Fläche zur Seite")
            ->and(dmContrast(dmHex("--btn-{$tone}-solid"), dmHex('--bg-surface')))->toBeGreaterThanOrEqual(3.0)
            ->and(dmContrast(dmHex("--btn-{$tone}-solid"), dmHex('--bg-accent-soft')))->toBeGreaterThanOrEqual(3.0);

        foreach (['soft', 'soft-hover', 'soft-press'] as $state) {
            expect(dmContrast(dmHex('--btn-on-soft'), dmHex("--btn-{$tone}-{$state}")))->toBeGreaterThanOrEqual(4.5, "{$tone} {$state}: Sekundär");
        }

        foreach ($surfaces as $surface) {
            expect(dmContrast(dmHex("--btn-{$tone}-text"), dmHex($surface)))->toBeGreaterThanOrEqual(4.5, "{$tone} Schrift auf {$surface}");
        }

        expect(dmContrast(dmHex("--btn-{$tone}-text"), dmHex('--bg-page')))->toBeGreaterThanOrEqual(3.0)
            ->and(dmContrast(dmHex("--btn-{$tone}-text-hover"), dmHex("--btn-{$tone}-soft")))->toBeGreaterThanOrEqual(4.5);
    }

    expect(dmContrast(dmHex('--btn-disabled-fg'), dmHex('--btn-disabled-bg')))->toBeGreaterThanOrEqual(4.5)
        ->and(dmContrast(dmHex('--border-input'), dmHex('--bg-accent-soft')))->toBeGreaterThanOrEqual(3.0)
        ->and(dmContrast(dmHex('--text-muted'), dmHex('--bg-accent-soft')))->toBeGreaterThanOrEqual(4.5)
        ->and(dmContrast(dmHex('--focus-ring'), dmHex('--bg-page')))->toBeGreaterThanOrEqual(3.0);
});

it('gives the night surface exactly the tones of the dark mode', function () {
    foreach (['green', 'lila'] as $tone) {
        foreach (['solid', 'solid-hover', 'solid-press', 'soft', 'soft-hover', 'soft-press', 'text', 'text-hover'] as $role) {
            expect(dmHex("--night-{$tone}-{$role}"))->toBe(dmHex("--btn-{$tone}-{$role}"), "{$tone} {$role}");
        }
    }

    expect(dmHex('--night-bg'))->toBe(dmHex('--bg-page'))
        ->and(dmHex('--night-on-solid'))->toBe(dmHex('--btn-on-solid'))
        ->and(dmHex('--night-on-soft'))->toBe(dmHex('--btn-on-soft'))
        ->and(dmHex('--night-disabled-bg'))->toBe(dmHex('--btn-disabled-bg'));
});
