<?php

namespace App\Support\Media;

use InvalidArgumentException;

class MissingImageSourceException extends InvalidArgumentException
{
    public static function forSrc(string $src): self
    {
        return new self("Das Bild \"{$src}\" hat keine Quellenangabe. Quelle per credit=\"…\" angeben oder im Manifest (sources.json) eintragen.");
    }

    public static function missingAlt(string $src): self
    {
        return new self("Das Bild \"{$src}\" hat keinen Alt-Text. alt=\"…\" setzen oder mit alt=\"\" bewusst als dekorativ markieren.");
    }
}
