<?php

namespace App\Modules\Communication\Services;

use App\Modules\Communication\Exceptions\EmailTemplateRenderException;
use App\Modules\Communication\Models\EmailTemplateVersion;

final class EmailTemplateRenderer
{
    /**
     * @param array<string, string|int|float> $values
     * @return array{subject: string, html: string}
     */
    public function render(
        EmailTemplateVersion $version,
        array $values,
    ): array {
        $version->loadMissing(
            'template.placeholders'
        );

        $placeholders = $version->template
            ->placeholders
            ->where('is_active', true);

        $allowedKeys = $placeholders
            ->pluck('key')
            ->all();

        $usedKeys = $this->extractPlaceholderKeys(
            $version->subject . "\n" . $version->html
        );

        foreach ($usedKeys as $key) {
            if (! in_array($key, $allowedKeys, true)) {
                throw EmailTemplateRenderException::unknownPlaceholder(
                    $key
                );
            }
        }

        $requiredKeys = $placeholders
            ->where('is_required', true)
            ->pluck('key')
            ->all();

        foreach ($requiredKeys as $key) {
            if (! in_array($key, $usedKeys, true)) {
                throw EmailTemplateRenderException::missingRequiredPlaceholder(
                    $key
                );
            }
        }

        foreach (array_keys($values) as $key) {
            if (! in_array($key, $allowedKeys, true)) {
                throw EmailTemplateRenderException::unknownValue(
                    $key
                );
            }
        }

        foreach ($usedKeys as $key) {
            if (! array_key_exists($key, $values)) {
                throw EmailTemplateRenderException::missingValue(
                    $key
                );
            }
        }

        return [
            'subject' => $this->replace(
                content: $version->subject,
                values: $values,
                escapeHtml: false,
            ),

            'html' => $this->replace(
                content: $version->html,
                values: $values,
                escapeHtml: true,
            ),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function extractPlaceholderKeys(
        string $content,
    ): array {
        preg_match_all(
            '/{{\s*([A-Za-z0-9_.-]+)\s*}}/',
            $content,
            $matches,
        );

        return array_values(
            array_unique($matches[1] ?? [])
        );
    }

    /**
     * @param array<string, string|int|float> $values
     */
    private function replace(
        string $content,
        array $values,
        bool $escapeHtml,
    ): string {
        return preg_replace_callback(
            '/{{\s*([A-Za-z0-9_.-]+)\s*}}/',
            function (array $matches) use (
                $values,
                $escapeHtml,
            ): string {
                $value = (string) $values[$matches[1]];

                if (! $escapeHtml) {
                    return $value;
                }

                return htmlspecialchars(
                    $value,
                    ENT_QUOTES | ENT_SUBSTITUTE,
                    'UTF-8',
                );
            },
            $content,
        ) ?? $content;
    }
}