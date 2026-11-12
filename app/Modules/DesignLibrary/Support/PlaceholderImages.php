<?php

namespace App\Modules\DesignLibrary\Support;

use Illuminate\Support\Collection;

/**
 * Platzhalterbilder für Design-Bibliothek und Entwürfe.
 *
 * Namenskonvention (Ordner public/images/placeholder/):
 *
 *     <art>-<nn>.<endung>      z. B. hero-01.jpg, teaser-03.webp
 *
 * - <art>     eine der Arten aus KINDS (bestimmt Seitenverhältnis und Verwendung)
 * - <nn>      zweistellige laufende Nummer ab 01
 * - <endung>  jpg, jpeg, png, webp, avif oder svg
 *
 * Liegt kein nummeriertes Bild vor, greift der Fallback <art>.svg. Eine
 * angeforderte Nummer über dem Bestand beginnt wieder vorn (modulo).
 */
class PlaceholderImages
{
    /** @var array<string, array{ratio: string, size: string, use: string}> */
    public const KINDS = [
        'hero' => ['ratio' => '16:7', 'size' => '1600×700', 'use' => 'Hero (image, caption)'],
        'tile' => ['ratio' => '3:2', 'size' => '600×400', 'use' => 'Hero-Kacheln (panel)'],
        'teaser' => ['ratio' => '3:2', 'size' => '800×533', 'use' => 'Teaser'],
        'split' => ['ratio' => '4:3', 'size' => '1200×900', 'use' => 'Split-Abschnitte'],
        'contact' => ['ratio' => '4:3', 'size' => '1200×900', 'use' => 'Kontaktblock'],
        'portrait' => ['ratio' => '1:1', 'size' => '400×400', 'use' => 'Personen, Avatare'],
    ];

    public const EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'avif', 'svg'];

    public function __construct(
        private readonly string $directory,
        private readonly string $urlPrefix = '/images/placeholder',
    ) {}

    public static function make(): self
    {
        return new self(public_path('images/placeholder'));
    }

    /** URL des n-ten Bildes der Art (n beginnt bei 1). */
    public function url(string $kind, int $n = 1): string
    {
        $this->assertKind($kind);

        $files = $this->files($kind);

        if ($files->isEmpty()) {
            return $this->urlPrefix.'/'.$kind.'.svg';
        }

        $index = (max(1, $n) - 1) % $files->count();

        return $this->urlPrefix.'/'.$files->values()[$index];
    }

    /**
     * Nummerierte Dateien einer Art, sortiert.
     *
     * @return Collection<int, string> Dateinamen
     */
    public function files(string $kind): Collection
    {
        $this->assertKind($kind);

        $pattern = '/^'.preg_quote($kind, '/').'-\d{2,}\.('.implode('|', self::EXTENSIONS).')$/i';

        $names = is_dir($this->directory) ? scandir($this->directory) : [];

        return collect($names ?: [])
            ->filter(fn (string $name): bool => preg_match($pattern, $name) === 1)
            ->sort()
            ->values();
    }

    /**
     * Übersicht für Bibliothek und API.
     *
     * @return list<array{kind: string, ratio: string, size: string, use: string, pattern: string, files: list<string>, urls: list<string>, fallback: string}>
     */
    public function overview(): array
    {
        return collect(self::KINDS)
            ->map(fn (array $meta, string $kind): array => $meta + [
                'kind' => $kind,
                'pattern' => $kind.'-01.jpg',
                'files' => $this->files($kind)->all(),
                'urls' => $this->files($kind)->map(fn (string $f): string => $this->urlPrefix.'/'.$f)->all(),
                'fallback' => $this->urlPrefix.'/'.$kind.'.svg',
            ])
            ->values()
            ->all();
    }

    private function assertKind(string $kind): void
    {
        if (! array_key_exists($kind, self::KINDS)) {
            throw new \InvalidArgumentException("Unbekannte Platzhalter-Art \"{$kind}\". Erlaubt: ".implode(', ', array_keys(self::KINDS)));
        }
    }
}
