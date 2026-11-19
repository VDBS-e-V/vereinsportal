<?php

namespace App\Modules\MailBuilder\Support;

/**
 * Platzhalter in Betreff und Text der Mails, geschrieben als {name} oder {name|Standardwert}. Die Ersetzung maskiert die Werte;
 * welche Platzhalter eine Mail kennt, steht im Register der Systemmails (SystemMails).
 */
final class MailPlaceholders
{
    /** {name} oder {name|Standardwert}: Der Standardwert gilt, wenn der Platzhalter leer ist. */
    private const PATTERN = '/\{([a-z_]+)(?:\|([^{}|]{0,60}))?\}/u';

    /** Alle in einem Text verwendeten Platzhalter (auch die mit Standardwert {name|Standard}). @return list<string> */
    public static function used(string $text): array
    {
        preg_match_all(self::PATTERN, $text, $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * Setzt die Werte ein. Bekannte Platzhalter ohne Wert bleiben leer (oder nehmen den Standardwert {name|Standard}), unbekannte Schreibweisen bleiben stehen.
     * In HTML werden die Werte maskiert, außer den Schlüsseln in $raw (bereits bereinigtes HTML).
     *
     * @param  array<string, string>  $values
     * @param  list<string>  $raw
     */
    public static function render(string $text, array $values, bool $html = false, array $raw = []): string
    {
        return (string) preg_replace_callback(self::PATTERN, function (array $match) use ($values, $html, $raw): string {
            if (! array_key_exists($match[1], $values)) {
                return $match[0];
            }

            $value = (string) $values[$match[1]];

            if (trim($value) === '' && isset($match[2]) && $match[2] !== '') {
                return $html ? e($match[2]) : $match[2];
            }

            return $html && ! in_array($match[1], $raw, true) ? e($value) : $value;
        }, $text);
    }
}
