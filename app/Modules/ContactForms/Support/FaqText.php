<?php

namespace App\Modules\ContactForms\Support;

use Illuminate\Support\HtmlString;

/**
 * Einfache, sichere Auszeichnung für FAQ-Antworten (statt freiem HTML):
 *   Leerzeile = neuer Absatz, Zeilen mit „- “ = Liste, **fett**, [Text](/pfad) oder [Text](https://adresse).
 * Alles andere wird als Text ausgegeben (HTML-Zeichen werden maskiert). Links dürfen nur auf Pfade im Portal oder auf https-Adressen zeigen.
 */
final class FaqText
{
    public static function html(string $text): HtmlString
    {
        $blocks = preg_split('/\R{2,}/u', trim(e($text))) ?: [];
        $html = '';

        foreach ($blocks as $block) {
            $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', $block) ?: []), fn (string $line): bool => $line !== ''));

            if ($lines === []) {
                continue;
            }

            if (count(array_filter($lines, fn (string $line): bool => str_starts_with($line, '- '))) === count($lines)) {
                $html .= '<ul>'.implode('', array_map(fn (string $line): string => '<li>'.self::inline(substr($line, 2)).'</li>', $lines)).'</ul>';

                continue;
            }

            $html .= '<p>'.implode('<br>', array_map(fn (string $line): string => self::inline($line), $lines)).'</p>';
        }

        return new HtmlString($html);
    }

    /** Auf bereits maskiertem Text: fett und Links. */
    private static function inline(string $line): string
    {
        $line = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $line) ?? $line;

        return preg_replace_callback('/\[([^\]]+)\]\(((?:\/(?!\/)|https:\/\/)[^\s)]*)\)/u', fn (array $match): string => '<a href="'.$match[2].'">'.$match[1].'</a>', $line) ?? $line;
    }
}
