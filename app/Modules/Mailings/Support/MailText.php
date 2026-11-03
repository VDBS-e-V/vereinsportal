<?php

namespace App\Modules\Mailings\Support;

/** Textversion einer Mail aus dem bereinigten HTML des Editors: Absätze, Listen, Links als „Text (Adresse)“. Ohne Tags. */
final class MailText
{
    public static function fromHtml(string $html): string
    {
        $text = preg_replace_callback('/<a\b[^>]*\bhref=(["\'])(.*?)\1[^>]*>(.*?)<\/a>/is', function (array $match): string {
            $label = trim(html_entity_decode(strip_tags($match[3]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $url = trim(html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            if ($label === '' || $label === $url) {
                return $url;
            }

            return $label.' ('.$url.')';
        }, $html) ?? $html;

        $text = preg_replace('/<li\b[^>]*>/i', "\n- ", $text) ?? $text;
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text) ?? $text;
        $text = preg_replace('/<\/(p|h[1-6]|ul|ol|blockquote)>/i', "\n\n", $text) ?? $text;
        $text = preg_replace('/<hr\b[^>]*>/i', "\n----\n", $text) ?? $text;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+\n/', "\n", $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }
}
