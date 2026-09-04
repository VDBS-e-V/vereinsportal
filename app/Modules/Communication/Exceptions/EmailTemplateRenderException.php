<?php

namespace App\Modules\Communication\Exceptions;

use RuntimeException;

final class EmailTemplateRenderException extends RuntimeException
{
    public static function unknownPlaceholder(string $key): self
    {
        return new self(
            "Der Placeholder '{$key}' ist für dieses E-Mail-Template nicht freigegeben."
        );
    }

    public static function missingRequiredPlaceholder(string $key): self
    {
        return new self(
            "Der erforderliche Placeholder '{$key}' fehlt im E-Mail-Template."
        );
    }

    public static function missingValue(string $key): self
    {
        return new self(
            "Für den Placeholder '{$key}' wurde kein Wert bereitgestellt."
        );
    }

    public static function unknownValue(string $key): self
    {
        return new self(
            "Für den nicht freigegebenen Placeholder '{$key}' wurde ein Wert bereitgestellt."
        );
    }
}
