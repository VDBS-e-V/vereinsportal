<?php

namespace App\Modules\MailBuilder\Support;

use App\Support\Html\RichText;
use Illuminate\Support\Str;

/**
 * Das Blockformat einer Mail: {version, preheader, blocks: [{id, type, props, visible_if}]}.
 * sanitize() macht aus beliebiger Eingabe immer ein gültiges Dokument: unbekannte Blöcke und Eigenschaften fallen weg, Texte werden gekürzt,
 * Rich-Text wird über die Positivliste bereinigt, Ziele müssen http(s), mailto, tel oder ein Platzhalter sein.
 */
final class BlockSchema
{
    public const VERSION = 1;

    public const MAX_BLOCKS = 60;

    /** @var array<string, string> Blocktyp => Bezeichnung */
    public const TYPES = [
        'heading' => 'Überschrift',
        'text' => 'Text',
        'button' => 'Button',
        'image' => 'Bild',
        'divider' => 'Trennlinie',
        'spacer' => 'Abstand',
        'callout' => 'Hinweisbox',
        'code' => 'Code-Box',
        'facts' => 'Angaben-Tabelle',
        'list' => 'Aufzählung',
        'quote' => 'Zitat',
        'columns' => 'Zwei Spalten',
        'signature' => 'Signatur',
        'repeat' => 'Wiederholer (Liste)',
    ];

    public const TONES = ['info', 'warning', 'success'];

    /** @return array{version: int, preheader: string, blocks: list<array<string, mixed>>} */
    public static function empty(): array
    {
        return ['version' => self::VERSION, 'preheader' => '', 'blocks' => []];
    }

    /** Ein neuer Block mit sinnvollen Vorgaben (für den Builder). @return array<string, mixed> */
    public static function make(string $type): array
    {
        $props = match ($type) {
            'heading' => ['text' => 'Überschrift', 'level' => 2, 'align' => 'left'],
            'text' => ['html' => '<p>Text</p>'],
            'button' => ['label' => 'Öffnen', 'url' => '{link}', 'align' => 'left', 'style' => 'primary'],
            'image' => ['asset_id' => null, 'alt' => '', 'link' => '', 'width' => 560, 'align' => 'center'],
            'spacer' => ['height' => 24],
            'callout' => ['tone' => 'info', 'html' => '<p>Hinweis</p>'],
            'code' => ['label' => 'Ihr Code', 'value' => '{code}'],
            'facts' => ['rows' => [['label' => 'Angabe', 'value' => 'Wert']]],
            'list' => ['items' => ['Punkt'], 'ordered' => false],
            'quote' => ['html' => '<p>Zitat</p>'],
            'columns' => ['left' => '<p>Links</p>', 'right' => '<p>Rechts</p>'],
            'signature' => ['html' => '<p>Mit freundlichen Grüßen<br>Der Vorstand</p>'],
            'repeat' => ['placeholder' => 'termine', 'template' => '{item}', 'ordered' => false],
            default => [],
        };

        return ['id' => Str::lower(Str::random(8)), 'type' => $type, 'props' => $props, 'visible_if' => null];
    }

    /**
     * @return array{version: int, preheader: string, blocks: list<array<string, mixed>>}
     */
    public static function sanitize(mixed $doc): array
    {
        $out = self::empty();

        if (! is_array($doc)) {
            return $out;
        }

        $out['preheader'] = self::str($doc['preheader'] ?? '', 200);
        $seen = [];

        foreach (array_slice(array_values(is_array($doc['blocks'] ?? null) ? $doc['blocks'] : []), 0, self::MAX_BLOCKS) as $block) {
            if (! is_array($block) || ! is_string($block['type'] ?? null) || ! isset(self::TYPES[$block['type']])) {
                continue;
            }

            $id = is_string($block['id'] ?? null) && preg_match('/^[a-z0-9_-]{1,40}$/', $block['id']) && ! isset($seen[$block['id']]) ? $block['id'] : Str::lower(Str::random(8));
            $seen[$id] = true;

            $out['blocks'][] = [
                'id' => $id,
                'type' => $block['type'],
                'props' => self::props($block['type'], is_array($block['props'] ?? null) ? $block['props'] : []),
                'visible_if' => self::visibility($block['visible_if'] ?? null),
            ];
        }

        return $out;
    }

    /** Ist das Ziel erlaubt? Ein Platzhalter allein (z. B. {link}) oder eine Adresse mit http, https, mailto oder tel. */
    public static function safeUrl(string $url): bool
    {
        $url = trim($url);

        if ($url === '') {
            return false;
        }

        if (preg_match('/^\{[a-z_]+(\|[^{}|]{0,60})?\}$/u', $url)) {
            return true;
        }

        return (bool) preg_match('/^(https?:\/\/[^\s<>"\'\\\\]+|mailto:[^\s<>"\'\\\\]+|tel:[+0-9 ()\-]+)$/i', $url);
    }

    /**
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>
     */
    private static function props(string $type, array $props): array
    {
        $align = fn (mixed $value): string => in_array($value, ['left', 'center'], true) ? (string) $value : 'left';

        return match ($type) {
            'heading' => ['text' => self::str($props['text'] ?? '', 200), 'level' => max(1, min(3, (int) ($props['level'] ?? 2))), 'align' => $align($props['align'] ?? null)],
            'text' => ['html' => self::html($props['html'] ?? '')],
            'button' => [
                'label' => self::str($props['label'] ?? '', 80),
                'url' => self::url($props['url'] ?? ''),
                'align' => $align($props['align'] ?? null),
                'style' => ($props['style'] ?? null) === 'secondary' ? 'secondary' : 'primary',
            ],
            'image' => [
                'asset_id' => is_numeric($props['asset_id'] ?? null) && (int) $props['asset_id'] > 0 ? (int) $props['asset_id'] : null,
                'alt' => self::str($props['alt'] ?? '', 200),
                'link' => self::url($props['link'] ?? ''),
                'width' => max(40, min(560, (int) ($props['width'] ?? 560))),
                'align' => ($props['align'] ?? null) === 'left' ? 'left' : 'center',
            ],
            'spacer' => ['height' => max(8, min(64, (int) ($props['height'] ?? 24)))],
            'callout' => ['tone' => in_array($props['tone'] ?? null, self::TONES, true) ? (string) $props['tone'] : 'info', 'html' => self::html($props['html'] ?? '')],
            'code' => ['label' => self::str($props['label'] ?? '', 80), 'value' => self::str($props['value'] ?? '', 100)],
            'facts' => ['rows' => self::rows($props['rows'] ?? [])],
            'list' => ['items' => self::items($props['items'] ?? []), 'ordered' => (bool) ($props['ordered'] ?? false)],
            'quote' => ['html' => self::html($props['html'] ?? '')],
            'columns' => ['left' => self::html($props['left'] ?? ''), 'right' => self::html($props['right'] ?? '')],
            'signature' => ['html' => self::html($props['html'] ?? '')],
            'repeat' => [
                'placeholder' => preg_match('/^[a-z_]{1,40}$/', (string) ($props['placeholder'] ?? '')) ? (string) $props['placeholder'] : '',
                'template' => self::str($props['template'] ?? '{item}', 200) ?: '{item}',
                'ordered' => (bool) ($props['ordered'] ?? false),
            ],
            default => [],
        };
    }

    /** @return array{placeholder: string, when: string}|null */
    private static function visibility(mixed $value): ?array
    {
        if (! is_array($value) || ! preg_match('/^[a-z_]{1,40}$/', (string) ($value['placeholder'] ?? ''))) {
            return null;
        }

        return ['placeholder' => (string) $value['placeholder'], 'when' => ($value['when'] ?? 'set') === 'empty' ? 'empty' : 'set'];
    }

    private static function str(mixed $value, int $max): string
    {
        return mb_substr(trim((string) preg_replace('/[\x00-\x1F\x7F]+/u', ' ', is_scalar($value) ? (string) $value : '')), 0, $max);
    }

    private static function html(mixed $value): string
    {
        return mb_substr((string) RichText::clean(is_string($value) ? $value : ''), 0, 20000);
    }

    private static function url(mixed $value): string
    {
        $url = self::str($value, 500);

        return $url !== '' && self::safeUrl($url) ? $url : '';
    }

    /** @return list<array{label: string, value: string}> */
    private static function rows(mixed $rows): array
    {
        $out = [];

        foreach (array_slice(is_array($rows) ? array_values($rows) : [], 0, 20) as $row) {
            if (is_array($row) && (self::str($row['label'] ?? '', 80) !== '' || self::str($row['value'] ?? '', 300) !== '')) {
                $out[] = ['label' => self::str($row['label'] ?? '', 80), 'value' => self::str($row['value'] ?? '', 300)];
            }
        }

        return $out;
    }

    /** @return list<string> */
    private static function items(mixed $items): array
    {
        $out = [];

        foreach (array_slice(is_array($items) ? array_values($items) : [], 0, 30) as $item) {
            $text = self::str($item, 300);

            if ($text !== '') {
                $out[] = $text;
            }
        }

        return $out;
    }
}
