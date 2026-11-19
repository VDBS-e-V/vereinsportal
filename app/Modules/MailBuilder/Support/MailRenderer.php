<?php

namespace App\Modules\MailBuilder\Support;

use App\Modules\Mailings\Support\MailPlaceholders;
use App\Modules\Mailings\Support\MailText;
use App\Support\Html\RichText;
use Stringable;

/**
 * Macht aus einem Blockdokument und dem Mail-Design eine fertige Mail: HTML (Tabellen, Inline-Styles, 600 px, Dunkelmodus, Vorschautext)
 * und eine Textfassung. Werte der Platzhalter werden maskiert; Listen (z. B. Termine) füllen den Wiederholer-Block.
 */
final class MailRenderer
{
    /** @var array<string, string> */
    private array $scalars = [];

    /** @var array<string, list<string>> */
    private array $lists = [];

    /** @var list<string> */
    private array $raw = [];

    /** @var array<string, mixed> */
    private array $s;

    /** @var array<int, string> Bild-ID => öffentliche Adresse */
    private array $assets = [];

    /**
     * @param  array<string, mixed>  $doc  Blockdokument (wird bereinigt)
     * @param  array<string, mixed>  $settings  Design (LayoutSettings, optional mit logo_url)
     * @param  array<string, mixed>  $values  Platzhalter => Text oder Liste
     * @param  array{subject?: string, force_dark?: bool, raw?: list<string>, footer_extra?: string, assets?: array<int, string>, allowed?: list<string>, protected?: list<string>}  $options
     */
    public static function render(array $doc, array $settings, array $values = [], array $options = []): RenderedMail
    {
        return (new self)->build(BlockSchema::sanitize($doc), $settings, $values, $options);
    }

    /**
     * @param  array{version: int, preheader: string, blocks: list<array<string, mixed>>}  $doc
     * @param  array<string, mixed>  $settings
     * @param  array<string, mixed>  $values
     * @param  array<string, mixed>  $options
     */
    private function build(array $doc, array $settings, array $values, array $options): RenderedMail
    {
        $this->s = LayoutSettings::normalize($settings) + ['logo_url' => is_string($settings['logo_url'] ?? null) ? $settings['logo_url'] : null];
        foreach ((array) ($options['assets'] ?? []) as $id => $url) {
            if (is_int($id) && is_string($url)) {
                $this->assets[$id] = $url;
            }
        }

        $this->raw = array_values(array_filter((array) ($options['raw'] ?? []), 'is_string'));

        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $this->lists[(string) $key] = array_values(array_map(fn (mixed $item): string => is_scalar($item) || $item instanceof Stringable ? (string) $item : '', $value));
            } elseif (is_scalar($value) || $value instanceof Stringable || $value === null) {
                $this->scalars[(string) $key] = (string) $value;
            }
        }

        $subject = trim((string) preg_replace('/\s+/u', ' ', MailPlaceholders::render((string) ($options['subject'] ?? ''), $this->scalars)));
        $preheader = $this->plain($doc['preheader']);

        $htmlBlocks = [];
        $textBlocks = [];

        foreach ($doc['blocks'] as $block) {
            if (! $this->visible($block['visible_if'])) {
                continue;
            }

            $rendered = $this->block($block['type'], $block['props']);

            if ($rendered !== null) {
                $htmlBlocks[] = $rendered[0];
                $textBlocks[] = $rendered[1];
            }
        }

        $footerHtml = $this->rich((string) $this->s['footer_html']).(string) ($options['footer_extra'] ?? '');
        $text = trim(implode("\n\n", array_filter([...$textBlocks, '-- ', MailText::fromHtml($footerHtml)], fn (string $part): bool => $part !== '')));
        $text = trim((string) preg_replace('/\n{3,}/', "\n\n", $text));

        return new RenderedMail($subject, $this->document($subject, $preheader, implode("\n", $htmlBlocks), $footerHtml, (bool) ($options['force_dark'] ?? false)), $text, MailCheck::run($doc, $this->s, $values, $options));
    }

    /** @param array{placeholder: string, when: string}|null $rule */
    private function visible(?array $rule): bool
    {
        if ($rule === null) {
            return true;
        }

        $set = trim($this->scalars[$rule['placeholder']] ?? '') !== '' || ($this->lists[$rule['placeholder']] ?? []) !== [];

        return $rule['when'] === 'empty' ? ! $set : $set;
    }

    /**
     * @param  array<string, mixed>  $p
     * @return array{0: string, 1: string}|null HTML und Text des Blocks
     */
    private function block(string $type, array $p): ?array
    {
        $theme = $this->s;

        switch ($type) {
            case 'heading':
                $text = $this->plain((string) $p['text']);
                $size = [1 => 26, 2 => 21, 3 => 17][(int) $p['level']];

                return $text === '' ? null : [$this->row('<h'.$p['level'].' style="margin:0;font-size:'.$size.'px;line-height:1.3;font-weight:bold;text-align:'.$p['align'].';" class="vdbs-text">'.e($text).'</h'.$p['level'].'>', 12), mb_strtoupper($text)];

            case 'text':
                $html = $this->rich((string) $p['html']);

                return trim($html) === '' ? null : [$this->row($this->inline($html)), MailText::fromHtml($html)];

            case 'button':
                $url = $this->url((string) $p['url']);
                $label = $this->plain((string) $p['label']);

                if ($url === null || $label === '') {
                    return null;
                }

                $primary = $p['style'] === 'primary';
                $bg = $primary ? $theme['button'] : $theme['card'];
                $fg = $primary ? $theme['button_text'] : $theme['button'];
                $button = '<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="'.$p['align'].'" style="margin:0;"><tr><td style="background:'.$bg.';border:2px solid '.$theme['button'].';border-radius:'.$theme['radius'].'px;" class="'.($primary ? 'vdbs-button' : 'vdbs-button-secondary').'"><a href="'.e($url).'" style="display:inline-block;padding:12px 24px;font-size:16px;font-weight:bold;color:'.$fg.';text-decoration:none;border-radius:'.$theme['radius'].'px;" class="'.($primary ? 'vdbs-button-text' : 'vdbs-link').'">'.e($label).'</a></td></tr></table>';

                return [$this->row($button), $label.': '.$url];

            case 'image':
                $url = $this->imageUrl($p['asset_id']);

                if ($url === null) {
                    return null;
                }

                $img = '<img src="'.e($url).'" alt="'.e($p['alt']).'" width="'.$p['width'].'" style="display:block;border:0;max-width:100%;height:auto;'.($p['align'] === 'center' ? 'margin:0 auto;' : '').'">';
                $link = $this->url((string) $p['link']);

                return [$this->row($link === null ? $img : '<a href="'.e($link).'">'.$img.'</a>'), $p['alt'] !== '' ? '['.$p['alt'].']' : ''];

            case 'divider':
                return [$this->row('<hr style="border:0;border-top:1px solid '.$theme['accent'].';margin:0;">', 8), '----'];

            case 'spacer':
                return ['<tr><td style="height:'.$p['height'].'px;line-height:'.$p['height'].'px;font-size:1px;">&nbsp;</td></tr>', ''];

            case 'callout':
                $html = $this->rich((string) $p['html']);
                $tones = ['info' => ['#e0f2fe', '#0369a1'], 'warning' => ['#fef3c7', '#b45309'], 'success' => ['#dcfce7', '#15803d']];
                [$bg, $line] = $tones[$p['tone']];

                return trim($html) === '' ? null : [$this->row('<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="background:'.$bg.';border-left:4px solid '.$line.';padding:12px 16px;color:#0f172a;border-radius:'.$theme['radius'].'px;">'.$this->inline($html, '#0f172a', '#0f172a').'</td></tr></table>'), MailText::fromHtml($html)];

            case 'code':
                $value = $this->plain((string) $p['value']);

                if ($value === '') {
                    return null;
                }

                $label = $this->plain((string) $p['label']);
                $html = ($label !== '' ? '<p style="margin:0 0 6px;font-size:14px;" class="vdbs-muted">'.e($label).'</p>' : '').'<p style="margin:0;font-family:\'Courier New\',monospace;font-size:28px;letter-spacing:6px;font-weight:bold;" class="vdbs-text">'.e($value).'</p>';

                return [$this->row($html), ($label !== '' ? $label.': ' : '').$value];

            case 'facts':
                $rows = '';
                $text = [];

                foreach ($p['rows'] as $row) {
                    $label = $this->plain((string) $row['label']);
                    $value = $this->plain((string) $row['value']);

                    if ($label === '' && $value === '') {
                        continue;
                    }

                    $rows .= '<tr><th align="left" scope="row" style="padding:6px 12px 6px 0;font-size:14px;vertical-align:top;font-weight:bold;" class="vdbs-muted">'.e($label).'</th><td style="padding:6px 0;font-size:16px;vertical-align:top;" class="vdbs-text">'.e($value).'</td></tr>';
                    $text[] = $label.': '.$value;
                }

                return $rows === '' ? null : [$this->row('<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">'.$rows.'</table>'), implode("\n", $text)];

            case 'list':
                $items = array_values(array_filter(array_map(fn (string $item): string => $this->plain($item), $p['items']), fn (string $item): bool => $item !== ''));

                return $items === [] ? null : $this->listBlock($items, (bool) $p['ordered']);

            case 'quote':
                $html = $this->rich((string) $p['html']);

                return trim($html) === '' ? null : [$this->row('<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="border-left:4px solid '.$theme['accent'].';padding:4px 0 4px 16px;font-style:italic;">'.$this->inline($html).'</td></tr></table>'), MailText::fromHtml($html)];

            case 'columns':
                $left = $this->rich((string) $p['left']);
                $right = $this->rich((string) $p['right']);

                if (trim($left.$right) === '') {
                    return null;
                }

                $cells = '<td class="vdbs-col" width="50%" valign="top" style="padding-right:8px;">'.$this->inline($left).'</td><td class="vdbs-col" width="50%" valign="top" style="padding-left:8px;">'.$this->inline($right).'</td>';

                return [$this->row('<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>'.$cells.'</tr></table>'), trim(MailText::fromHtml($left)."\n\n".MailText::fromHtml($right))];

            case 'signature':
                $html = $this->rich((string) $p['html']);

                return trim($html) === '' ? null : [$this->row($this->inline($html)), MailText::fromHtml($html)];

            case 'repeat':
                $items = array_values(array_filter(array_map(fn (string $item): string => $this->plain(str_replace('{item}', $item, MailPlaceholders::render((string) $p['template'], $this->scalars))), $this->lists[$p['placeholder']] ?? []), fn (string $item): bool => $item !== ''));

                return $items === [] ? null : $this->listBlock($items, (bool) $p['ordered']);
        }

        return null;
    }

    /** @param list<string> $items @return array{0: string, 1: string} */
    private function listBlock(array $items, bool $ordered): array
    {
        $tag = $ordered ? 'ol' : 'ul';
        $html = '<'.$tag.' style="margin:0;padding-left:22px;font-size:16px;line-height:1.5;" class="vdbs-text">'.implode('', array_map(fn (string $item): string => '<li style="margin:0 0 4px;">'.e($item).'</li>', $items)).'</'.$tag.'>';
        $text = implode("\n", array_map(fn (string $item, int $i): string => ($ordered ? ($i + 1).'. ' : '- ').$item, $items, array_keys($items)));

        return [$this->row($html), $text];
    }

    private function row(string $inner, int $bottom = 16): string
    {
        return '<tr><td class="vdbs-pad" style="padding:0 32px '.$bottom.'px 32px;font-size:16px;line-height:1.5;">'.$inner.'</td></tr>';
    }

    /** Platzhalter in einem einfachen Text ersetzen (Ergebnis ist Klartext, vor der Ausgabe mit e() maskieren). */
    private function plain(string $text): string
    {
        return trim(MailPlaceholders::render($text, $this->scalars));
    }

    /** Platzhalter in bereinigtem Rich-Text ersetzen; Werte werden maskiert, außer den als „raw“ gekennzeichneten. */
    private function rich(string $html): string
    {
        return MailPlaceholders::render($html, $this->scalars, true, $this->raw);
    }

    /** Ziel mit ersetzten Platzhaltern; null, wenn es danach nicht mehr erlaubt ist. */
    private function url(string $url): ?string
    {
        if ($url === '') {
            return null;
        }

        $resolved = trim(MailPlaceholders::render($url, $this->scalars));

        return BlockSchema::safeUrl($resolved) && ! preg_match('/^\{[a-z_]+/u', $resolved) ? $resolved : null;
    }

    private function imageUrl(mixed $assetId): ?string
    {
        return is_int($assetId) ? ($this->assets[$assetId] ?? null) : null;
    }

    /** Inline-Styles an die Elemente des bereinigten Rich-Texts hängen (Mailclients kennen kein externes CSS). */
    private function inline(string $html, ?string $color = null, ?string $linkColor = null): string
    {
        $link = $linkColor ?? $this->s['link'];
        $class = $color === null ? ' class="vdbs-text"' : '';
        $color = $color ?? $this->s['text'];
        $map = [
            '/<p>/i' => '<p style="margin:0 0 12px;font-size:16px;line-height:1.5;color:'.$color.';"'.$class.'>',
            '/<(h2|h3|h4)>/i' => '<$1 style="margin:0 0 10px;line-height:1.3;color:'.$color.';"'.$class.'>',
            '/<ul>/i' => '<ul style="margin:0 0 12px;padding-left:22px;color:'.$color.';"'.$class.'>',
            '/<ol>/i' => '<ol style="margin:0 0 12px;padding-left:22px;color:'.$color.';"'.$class.'>',
            '/<li>/i' => '<li style="margin:0 0 4px;font-size:16px;line-height:1.5;">',
            '/<blockquote>/i' => '<blockquote style="margin:0 0 12px;padding-left:12px;border-left:3px solid '.$this->s['accent'].';">',
            '/<hr\s*\/?>/i' => '<hr style="border:0;border-top:1px solid '.$this->s['accent'].';margin:12px 0;">',
            '/<a href=/i' => '<a style="color:'.$link.';text-decoration:underline;" class="vdbs-link" href=',
        ];

        return (string) preg_replace(array_keys($map), array_values($map), $html);
    }

    private function document(string $subject, string $preheader, string $body, string $footerHtml, bool $forceDark): string
    {
        $s = $this->s;
        $font = LayoutSettings::fontStack((string) $s['font']);
        $width = (int) $s['width'];
        $dark = LayoutSettings::DARK;
        $darkCss = '.vdbs-bg{background:'.$dark['background'].' !important}.vdbs-card{background:'.$dark['card'].' !important}.vdbs-text,.vdbs-text *{color:'.$dark['text'].' !important}.vdbs-muted,.vdbs-muted *{color:'.$dark['muted'].' !important}.vdbs-link{color:'.$dark['link'].' !important}.vdbs-button-secondary{background:'.$dark['card'].' !important}';
        $css = '@media only screen and (max-width:'.($width + 20).'px){.vdbs-card{width:100% !important;border-radius:0 !important}.vdbs-pad{padding-left:20px !important;padding-right:20px !important}.vdbs-col{display:block !important;width:100% !important;padding:0 0 12px 0 !important}}'
            .($forceDark ? $darkCss : '@media (prefers-color-scheme:dark){'.$darkCss.'}');

        $logo = '';

        if (is_string($s['logo_url'] ?? null) && $s['logo_url'] !== '') {
            $logo = '<tr><td class="vdbs-pad" style="padding:28px 32px 16px 32px;"><img src="'.e($s['logo_url']).'" alt="'.e($s['logo_alt']).'" width="'.(int) $s['logo_width'].'" style="display:block;border:0;max-width:100%;height:auto;"></td></tr>';
        } elseif ($s['header_text'] !== '') {
            $logo = '<tr><td class="vdbs-pad vdbs-text" style="padding:28px 32px 16px 32px;font-size:18px;font-weight:bold;">'.e($s['header_text']).'</td></tr>';
        } else {
            $logo = '<tr><td style="height:28px;line-height:28px;font-size:1px;">&nbsp;</td></tr>';
        }

        $footer = trim($footerHtml) === '' ? '' : '<tr><td class="vdbs-pad vdbs-muted" style="padding:8px 32px 28px 32px;font-size:13px;line-height:1.5;border-top:1px solid '.$s['accent'].';">'.$this->footerInline($footerHtml).'</td></tr>';
        $pre = $preheader !== '' ? '<div style="display:none;max-height:0;overflow:hidden;opacity:0;mso-hide:all;font-size:1px;line-height:1px;">'.e($preheader).'</div>' : '';

        return '<!DOCTYPE html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="color-scheme" content="light dark"><meta name="supported-color-schemes" content="light dark"><title>'.e($subject).'</title><style>'.$css.'</style></head>'
            .'<body class="vdbs-bg" style="margin:0;padding:0;background:'.$s['background'].';font-family:'.$font.';color:'.$s['text'].';">'.$pre
            .'<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="vdbs-bg" style="background:'.$s['background'].';"><tr><td align="center" style="padding:24px 12px;">'
            .'<table role="presentation" class="vdbs-card" width="'.$width.'" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:'.$width.'px;background:'.$s['card'].';border-radius:'.$s['radius'].'px;border-top:4px solid '.$s['accent'].';">'
            .$logo.$body.$footer.'</table></td></tr></table></body></html>';
    }

    private function footerInline(string $html): string
    {
        return (string) preg_replace(['/<p>/i', '/<a href=/i'], ['<p style="margin:0 0 6px;font-size:13px;line-height:1.5;color:'.$this->s['muted'].';" class="vdbs-muted">', '<a style="color:'.$this->s['link'].';" class="vdbs-link" href='], $html);
    }
}
