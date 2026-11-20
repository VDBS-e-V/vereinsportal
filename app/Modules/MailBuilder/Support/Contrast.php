<?php

namespace App\Modules\MailBuilder\Support;

/** Kontrastverhältnis nach WCAG (Farben als #rrggbb). */
final class Contrast
{
    public static function ratio(string $a, string $b): float
    {
        $la = self::luminance($a);
        $lb = self::luminance($b);

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    private static function luminance(string $hex): float
    {
        $hex = ltrim($hex, '#');
        $channels = array_map(function (string $part): float {
            $value = hexdec($part) / 255;

            return $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }, str_split(strlen($hex) === 6 ? $hex : '000000', 2));

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }
}
