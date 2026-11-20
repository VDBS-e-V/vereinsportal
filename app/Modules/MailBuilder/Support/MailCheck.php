<?php

namespace App\Modules\MailBuilder\Support;

use App\Modules\Mailings\Support\MailPlaceholders;

/**
 * Prüft ein Blockdokument vor dem Speichern: unbekannte und fehlende Pflichtplatzhalter, fehlende Alternativtexte, Kontraste, leere Blöcke, Ziele.
 * Stufen: "error" verhindert das Speichern, "warning" ist ein Hinweis.
 */
final class MailCheck
{
    /**
     * @param  array{version: int, preheader: string, blocks: list<array<string, mixed>>}  $doc
     * @param  array<string, mixed>  $settings
     * @param  array<string, mixed>  $values
     * @param  array<string, mixed>  $options  allowed (erlaubte Platzhalter), protected (Pflichtplatzhalter), subject
     * @return list<array{level: string, message: string}>
     */
    public static function run(array $doc, array $settings, array $values = [], array $options = []): array
    {
        $issues = [];
        $add = function (string $level, string $message) use (&$issues): void {
            $issues[] = ['level' => $level, 'message' => $message];
        };

        $json = (string) json_encode($doc['blocks'], JSON_UNESCAPED_UNICODE);
        $subject = (string) ($options['subject'] ?? '');

        if ($doc['blocks'] === []) {
            $add('error', 'Die Mail hat noch keinen Inhalt.');
        }

        if (isset($options['allowed']) && is_array($options['allowed'])) {
            $allowed = array_merge(array_values(array_filter($options['allowed'], 'is_string')), ['item']);

            foreach (array_unique(array_merge(MailPlaceholders::used($json), MailPlaceholders::used($subject), MailPlaceholders::used($doc['preheader']))) as $name) {
                if (! in_array($name, $allowed, true)) {
                    $add('error', 'Der Platzhalter {'.$name.'} gibt es in dieser Mail nicht.');
                }
            }
        }

        foreach ((array) ($options['protected'] ?? []) as $name) {
            if (is_string($name) && ! in_array($name, MailPlaceholders::used($json), true)) {
                $add('error', 'Der Platzhalter {'.$name.'} muss in der Mail stehen, sonst fehlt eine wichtige Angabe.');
            }
        }

        foreach ($doc['blocks'] as $i => $block) {
            $label = BlockSchema::TYPES[$block['type']].' '.($i + 1);
            $p = $block['props'];

            if ($block['type'] === 'image') {
                if (trim((string) $p['alt']) === '') {
                    $add('error', $label.': Bitte einen Alternativtext angeben.');
                }

                if ($p['asset_id'] === null) {
                    $add('warning', $label.': Es ist noch kein Bild gewählt.');
                }
            }

            if ($block['type'] === 'button' && ($p['url'] === '' || trim((string) $p['label']) === '')) {
                $add('error', $label.': Button braucht Beschriftung und ein gültiges Ziel (https, mailto, tel oder Platzhalter).');
            }

            if ($block['type'] === 'heading' && trim((string) $p['text']) === '') {
                $add('warning', $label.' ist leer.');
            }

            if (in_array($block['type'], ['text', 'callout', 'quote', 'signature'], true) && trim(strip_tags((string) $p['html'])) === '') {
                $add('warning', $label.' ist leer.');
            }
        }

        $s = LayoutSettings::normalize($settings);

        foreach ([['text', 'card', 'Text auf der Mailfläche', 4.5], ['link', 'card', 'Links auf der Mailfläche', 4.5], ['button_text', 'button', 'Button-Schrift auf dem Button', 4.5], ['muted', 'card', 'Nebentext (Fußzeile)', 4.5]] as [$fg, $bg, $name, $min]) {
            $ratio = Contrast::ratio((string) $s[$fg], (string) $s[$bg]);

            if ($ratio < $min) {
                $add('warning', 'Kontrast zu gering: '.$name.' ('.number_format($ratio, 1, ',', '').' : 1, nötig sind '.number_format($min, 1, ',', '').' : 1).');
            }
        }

        return $issues;
    }

    /** @param list<array{level: string, message: string}> $issues */
    public static function hasErrors(array $issues): bool
    {
        foreach ($issues as $issue) {
            if ($issue['level'] === 'error') {
                return true;
            }
        }

        return false;
    }
}
