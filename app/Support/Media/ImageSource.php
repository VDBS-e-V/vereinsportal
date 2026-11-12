<?php

namespace App\Support\Media;

/**
 * Quellenangabe eines Bildes.
 *
 * Jedes Bild im Portal braucht eine Quelle (siehe <x-vdbs.media.image>).
 * `label()` ist der Text, der sichtbar neben dem Bild steht.
 */
final readonly class ImageSource
{
    public function __construct(
        public string $source,
        public ?string $author = null,
        public ?string $url = null,
        public ?string $license = null,
        public ?string $credit = null,
        public bool $placeholder = false,
    ) {}

    /** @param array{source?: string, author?: ?string, url?: ?string, license?: ?string, credit?: ?string, placeholder?: bool} $data */
    public static function fromArray(array $data): self
    {
        return new self(
            source: (string) ($data['source'] ?? ''),
            author: $data['author'] ?? null,
            url: $data['url'] ?? null,
            license: $data['license'] ?? null,
            credit: $data['credit'] ?? null,
            placeholder: (bool) ($data['placeholder'] ?? false),
        );
    }

    /** Freitext-Quelle, z. B. aus dem Attribut credit="Bernd Wannenmacher". */
    public static function fromCredit(string $credit): self
    {
        return new self(source: $credit, credit: $credit);
    }

    /** Sichtbarer Text ohne Präfix: „Autor:in, Quelle“ oder der freie Credit-Text. */
    public function label(): string
    {
        if (filled($this->credit)) {
            return $this->credit;
        }

        $parts = array_filter([$this->author, $this->source], fn (?string $p): bool => filled($p));

        return implode(', ', array_unique($parts));
    }

    public function isComplete(): bool
    {
        return filled($this->label());
    }
}
