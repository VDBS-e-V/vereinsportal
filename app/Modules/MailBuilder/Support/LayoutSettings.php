<?php

namespace App\Modules\MailBuilder\Support;

use App\Support\Html\RichText;

/** Die Einstellungen des zentralen Mail-Designs: Standardwerte und Bereinigung. Nur mailsichere Schriften, Farben nur als #rrggbb. */
final class LayoutSettings
{
    /** @var array<string, string> Schlüssel => Schriftfamilie (nur Systemschriften, keine Webfonts) */
    public const FONTS = [
        'sans' => 'Arial, Helvetica, sans-serif',
        'serif' => 'Georgia, \'Times New Roman\', serif',
        'verdana' => 'Verdana, Geneva, sans-serif',
    ];

    /** @var array<string, string> Farbschlüssel => Bezeichnung */
    public const COLORS = [
        'background' => 'Hintergrund',
        'card' => 'Mailfläche',
        'text' => 'Text',
        'muted' => 'Nebentext',
        'link' => 'Links',
        'button' => 'Button',
        'button_text' => 'Button-Schrift',
        'accent' => 'Akzent (Kopf, Linien)',
    ];

    /** @var array<string, mixed> */
    public const DEFAULTS = [
        'width' => 600,
        'font' => 'sans',
        'background' => '#f1f5f9',
        'card' => '#ffffff',
        'text' => '#0f172a',
        'muted' => '#475569',
        'link' => '#057f5b',
        'button' => '#057f5b',
        'button_text' => '#ffffff',
        'accent' => '#2dc08e',
        'radius' => 6,
        'logo_asset_id' => null,
        'logo_width' => 160,
        'logo_alt' => 'Verband für Demokratiebildung und Bibliotheken an Schulen e. V.',
        'header_text' => '',
        'footer_html' => '<p>Verband für Demokratiebildung und Bibliotheken an Schulen e. V.</p>',
    ];

    /** Dunkelmodus: eigene Farben für Clients, die ihn unterstützen. @var array<string, string> */
    public const DARK = ['background' => '#0b1220', 'card' => '#111c2e', 'text' => '#e2e8f0', 'muted' => '#94a3b8', 'link' => '#5eead4'];

    /**
     * @param  array<string, mixed>|null  $input
     * @return array<string, mixed>
     */
    public static function normalize(?array $input): array
    {
        $input ??= [];
        $out = self::DEFAULTS;

        foreach (array_keys(self::COLORS) as $key) {
            $value = $input[$key] ?? null;

            if (is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', trim($value))) {
                $out[$key] = strtolower(trim($value));
            }
        }

        $font = $input['font'] ?? null;
        $out['font'] = is_string($font) && isset(self::FONTS[$font]) ? $font : self::DEFAULTS['font'];
        $out['width'] = max(480, min(700, (int) ($input['width'] ?? self::DEFAULTS['width'])));
        $out['radius'] = max(0, min(16, (int) ($input['radius'] ?? self::DEFAULTS['radius'])));
        $out['logo_width'] = max(40, min(400, (int) ($input['logo_width'] ?? self::DEFAULTS['logo_width'])));
        $logo = $input['logo_asset_id'] ?? null;
        $out['logo_asset_id'] = is_numeric($logo) && (int) $logo > 0 ? (int) $logo : null;
        $out['logo_alt'] = self::text($input['logo_alt'] ?? self::DEFAULTS['logo_alt'], 200);
        $out['header_text'] = self::text($input['header_text'] ?? '', 200);
        $footer = array_key_exists('footer_html', $input) ? RichText::clean(is_string($input['footer_html']) ? $input['footer_html'] : '') : self::DEFAULTS['footer_html'];
        $out['footer_html'] = (string) $footer;

        return $out;
    }

    public static function fontStack(string $key): string
    {
        return self::FONTS[$key] ?? self::FONTS['sans'];
    }

    private static function text(mixed $value, int $max): string
    {
        return mb_substr(trim((string) preg_replace('/[\x00-\x1F\x7F]+/u', ' ', is_scalar($value) ? (string) $value : '')), 0, $max);
    }
}
