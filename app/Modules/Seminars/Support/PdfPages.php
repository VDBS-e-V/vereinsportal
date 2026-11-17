<?php

namespace App\Modules\Seminars\Support;

/** Liest die Seitenzahl aus einer PDF-Datei (ohne Bibliothek). Bei ungewöhnlich gebauten Dateien liefert sie null; dann trägt man die Zahl im Druckplaner von Hand ein. */
final class PdfPages
{
    public static function count(string $path): ?int
    {
        if (! is_file($path) || filesize($path) > 60 * 1048576) {
            return null;
        }

        $content = (string) file_get_contents($path);

        if (! str_starts_with($content, '%PDF')) {
            return null;
        }

        $declared = 0;

        if (preg_match_all('#/Type\s*/Pages\b[^>]*?/Count\s+(\d+)#s', $content, $matches)) {
            $declared = max(array_map('intval', $matches[1]));
        }

        $leaves = (int) preg_match_all('#/Type\s*/Page(?![A-Za-z])#', $content);
        $pages = max($declared, $leaves);

        return $pages > 0 && $pages <= 5000 ? $pages : null;
    }
}
