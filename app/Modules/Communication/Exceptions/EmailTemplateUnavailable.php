<?php

namespace App\Modules\Communication\Exceptions;

use RuntimeException;

final class EmailTemplateUnavailable extends RuntimeException
{
    public static function missingOrInactive(
        string $templateKey,
    ): self {
        return new self(
            "Das E-Mail-Template '{$templateKey}' ist nicht für den Versand verfügbar."
        );
    }

    public static function withoutPublishedVersion(
        string $templateKey,
    ): self {
        return new self(
            "Für das E-Mail-Template '{$templateKey}' existiert keine veröffentlichte Version."
        );
    }
}