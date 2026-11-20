<?php

namespace App\Modules\MailBuilder\Support;

/**
 * Beschreibung einer Systemmail: Schlüssel, Name, Gruppe, Standardbetreff und Standardtext (als Blöcke) sowie die Platzhalter,
 * die in dieser Mail gelten (mit Beispielwert für die Vorschau). Geschützte Platzhalter (Links, Codes) müssen im Text stehen bleiben.
 */
final readonly class SystemMail
{
    /**
     * @param  array<string, array{label: string, sample: string|list<string>, protected?: bool, raw?: bool}>  $placeholders
     * @param  list<array<string, mixed>>  $blocks
     */
    public function __construct(
        public string $key,
        public string $name,
        public string $group,
        public string $description,
        public string $subject,
        public array $placeholders,
        public array $blocks,
        public string $preheader = '',
        public bool $security = false,
        public bool $mandatory = true,
    ) {}

    /** Namen aller erlaubten Platzhalter, einschließlich der allgemeinen. @return list<string> */
    public function allowed(): array
    {
        return array_values(array_unique([...array_keys(SystemMails::COMMON), ...array_keys($this->placeholders)]));
    }

    /** @return list<string> */
    public function protected(): array
    {
        return array_keys(array_filter($this->placeholders, fn (array $placeholder): bool => $placeholder['protected'] ?? false));
    }

    /** Platzhalter, deren Werte bereits fertiges HTML aus dem Code sind. @return list<string> */
    public function raw(): array
    {
        return array_keys(array_filter($this->placeholders, fn (array $placeholder): bool => $placeholder['raw'] ?? false));
    }

    /** Beispielwerte für die Vorschau im Builder. @return array<string, string|list<string>> */
    public function samples(): array
    {
        $values = array_map(fn (array $placeholder): string|array => $placeholder['sample'], $this->placeholders);

        return $values + array_map(fn (array $placeholder): string => $placeholder['sample'], SystemMails::COMMON);
    }

    /** @return array{version: int, preheader: string, blocks: list<array<string, mixed>>} */
    public function document(): array
    {
        return BlockSchema::sanitize(['preheader' => $this->preheader, 'blocks' => $this->blocks]);
    }
}
