<?php

namespace App\Support\Media;

/**
 * Manifest der Bildquellen (statische Bilder).
 *
 * Pro Ordner eine sources.json: { "dateiname.jpg": { author, source, url, license, placeholder } }.
 * Ordner stehen in config('images.manifests'). Später ersetzt/ergänzt ein DB-Modul "Media" diese Quelle.
 */
class ImageManifest
{
    /** @var array<string, ImageSource>|null */
    private ?array $entries = null;

    /** @param list<string> $files Pfade zu sources.json */
    public function __construct(private readonly array $files) {}

    public static function make(): self
    {
        return new self(config('images.manifests', []));
    }

    /** Sucht anhand des Dateinamens der URL (Pfad und Query werden ignoriert). */
    public function find(string $src): ?ImageSource
    {
        $name = basename((string) parse_url($src, PHP_URL_PATH));

        return $this->entries()[$name] ?? null;
    }

    /** @return array<string, ImageSource> */
    public function entries(): array
    {
        if ($this->entries !== null) {
            return $this->entries;
        }

        $this->entries = [];

        foreach ($this->files as $file) {
            if (! is_file($file)) {
                continue;
            }

            $data = json_decode((string) file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);

            foreach ($data as $name => $row) {
                $this->entries[$name] = ImageSource::fromArray($row);
            }
        }

        return $this->entries;
    }
}
