<?php

namespace App\Modules\System\Services;

use App\Modules\System\Support\Redactor;

/**
 * Liest die Anwendungs-Protokolldatei (storage/logs) für die Systemsteuerung: nur das Ende der neuesten Datei, neueste Einträge zuerst,
 * mit Filter nach Stufe und Suchtext. Geheimnisse werden geschwärzt. Die Datei wird nie verändert.
 */
final class SystemLogs
{
    public const MAX_BYTES = 2_000_000;

    /** Stufen von schwer nach leicht. */
    public const LEVELS = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];

    /** Stufen, die bei „ab Fehler“ gelten. */
    public const SEVERE = ['emergency', 'alert', 'critical', 'error'];

    /** Neueste Protokolldatei oder null. */
    public function file(): ?string
    {
        $files = glob(storage_path('logs/*.log')) ?: [];
        usort($files, fn (string $a, string $b): int => filemtime($b) <=> filemtime($a));

        return $files[0] ?? null;
    }

    /**
     * @return array{entries: list<array{time: string, env: string, level: string, message: string, detail: string}>, file: ?string, size: int, truncated: bool}
     */
    public function read(?string $level = null, ?string $search = null): array
    {
        $file = $this->file();

        if ($file === null || ! is_readable($file)) {
            return ['entries' => [], 'file' => null, 'size' => 0, 'truncated' => false];
        }

        $size = (int) filesize($file);
        $handle = fopen($file, 'rb');
        $truncated = $size > self::MAX_BYTES;

        if ($truncated) {
            fseek($handle, $size - self::MAX_BYTES);
        }

        $text = (string) stream_get_contents($handle);
        fclose($handle);

        $entries = $this->parse($text, $truncated);
        $search = trim((string) $search);
        $levels = match (true) {
            $level === 'ab-fehler' => self::SEVERE,
            in_array($level, self::LEVELS, true) => [$level],
            default => null,
        };

        $entries = array_values(array_filter($entries, function (array $entry) use ($levels, $search): bool {
            if ($levels !== null && ! in_array($entry['level'], $levels, true)) {
                return false;
            }

            return $search === '' || mb_stripos($entry['message'].' '.$entry['detail'], $search) !== false;
        }));

        return ['entries' => array_reverse($entries), 'file' => basename($file), 'size' => $size, 'truncated' => $truncated];
    }

    /** @return list<array{time: string, env: string, level: string, message: string, detail: string}> */
    private function parse(string $text, bool $dropFirstPartial): array
    {
        $lines = preg_split('/\r\n|\n|\r/', $text) ?: [];

        if ($dropFirstPartial) {
            array_shift($lines);
        }

        $entries = [];
        $current = null;

        foreach ($lines as $line) {
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2})[^\]]*\]\s+([\w\-]+)\.([A-Za-z]+):\s?(.*)$/', $line, $match) === 1) {
                if ($current !== null) {
                    $entries[] = $current;
                }

                $current = ['time' => str_replace('T', ' ', $match[1]), 'env' => $match[2], 'level' => strtolower($match[3]), 'message' => $match[4], 'detail' => ''];

                continue;
            }

            if ($current !== null && $line !== '') {
                $current['detail'] .= ($current['detail'] === '' ? '' : "\n").$line;
            }
        }

        if ($current !== null) {
            $entries[] = $current;
        }

        return array_map(fn (array $entry): array => [
            'time' => $entry['time'],
            'env' => $entry['env'],
            'level' => $entry['level'],
            'message' => Redactor::clean($entry['message'], 500),
            'detail' => Redactor::clean($entry['detail'], 4000),
        ], $entries);
    }
}
