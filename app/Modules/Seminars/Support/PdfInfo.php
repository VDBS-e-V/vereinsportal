<?php

namespace App\Modules\Seminars\Support;

use setasign\Fpdi\Fpdi;
use Throwable;

/** Liest die Seitenzahl einer PDF-Datei. Gelingt das nicht (verschlüsselt, ungewöhnlich gebaut), gibt es null; dann lässt sich die Datei nicht im Druckplaner verwenden. */
final class PdfInfo
{
    public static function pages(string $path): ?int
    {
        if (! is_file($path) || filesize($path) > 60 * 1048576) {
            return null;
        }

        try {
            $count = (new Fpdi)->setSourceFile($path);
        } catch (Throwable) {
            return null;
        }

        return $count > 0 && $count <= 2000 ? $count : null;
    }
}
