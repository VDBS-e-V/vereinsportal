<?php

namespace App\Modules\Communication\Services;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

final class EmailTemplateHtmlSanitizer
{
    private HtmlSanitizer $sanitizer;

    public function __construct()
    {
        $this->sanitizer = new HtmlSanitizer(
            (new HtmlSanitizerConfig)
                ->allowSafeElements()
                ->allowLinkSchemes([
                    'https',
                    'http',
                    'mailto',
                ])
                ->allowRelativeLinks()
        );
    }

    public function sanitize(string $html): string
    {
        [
            'html' => $maskedHtml,
            'replacements' => $replacements,
        ] = $this->maskPlaceholders($html);

        $sanitized = $this->sanitizer->sanitize(
            $maskedHtml
        );

        return strtr(
            $sanitized,
            $replacements,
        );
    }

    /**
     * Placeholder müssen vor dem HTML-Sanitizing maskiert werden.
     *
     * Beispiel:
     * href="{{ verification_url }}"
     *
     * Ohne Maskierung würde ein HTML-Sanitizer den Placeholder
     * möglicherweise nicht als gültige URL behandeln und href entfernen.
     *
     * @return array{
     *     html: string,
     *     replacements: array<string, string>
     * }
     */
    private function maskPlaceholders(
        string $html,
    ): array {
        $replacements = [];
        $index = 0;

        $maskedHtml = preg_replace_callback(
            '/{{\s*([A-Za-z0-9_.-]+)\s*}}/',
            function (array $matches) use (
                &$replacements,
                &$index,
            ): string {
                $token = sprintf(
                    'vdb-template-placeholder-%d',
                    $index
                );

                $replacements[$token] = sprintf(
                    '{{ %s }}',
                    $matches[1]
                );

                $index++;

                return $token;
            },
            $html,
        ) ?? $html;

        return [
            'html' => $maskedHtml,
            'replacements' => $replacements,
        ];
    }
}
