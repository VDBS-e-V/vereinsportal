<?php

namespace App\Modules\Communication\Exceptions;

use RuntimeException;

final class EmailTemplateCannotBePublished extends RuntimeException
{
    public static function missingSubject(): self
    {
        return new self(
            'Das E-Mail-Template kann ohne Betreff nicht veröffentlicht werden.'
        );
    }

    public static function missingHtml(): self
    {
        return new self(
            'Das E-Mail-Template kann ohne HTML-Inhalt nicht veröffentlicht werden.'
        );
    }

    public static function unknownPlaceholder(
        string $key,
    ): self {
        return new self(
            "Der Placeholder '{$key}' ist für dieses E-Mail-Template nicht freigegeben."
        );
    }

    public static function missingRequiredPlaceholder(
        string $key,
    ): self {
        return new self(
            "Der erforderliche Placeholder '{$key}' fehlt im E-Mail-Template."
        );
    }
}
