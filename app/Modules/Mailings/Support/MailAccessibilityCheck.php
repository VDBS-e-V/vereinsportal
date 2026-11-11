<?php

namespace App\Modules\Mailings\Support;

/**
 * Hinweise zur Lesbarkeit und Barrierefreiheit eines Textes aus dem Editor. Sie erscheinen in der Vorschau und sperren nichts:
 * nichtssagende Linktexte, leere oder übersprungene Überschriften, Bilder ohne Alternativtext, sehr lange Absätze.
 */
final class MailAccessibilityCheck
{
    /** Linktexte, die außerhalb des Satzes nichts sagen. */
    private const VAGUE_LINKS = ['hier', 'hier klicken', 'klicken sie hier', 'dort', 'link', 'mehr', 'mehr lesen', 'weiter', 'weiterlesen', 'klick', 'diesen link', 'dieser link'];

    private const LONG_PARAGRAPH_WORDS = 120;

    /** @return list<string> */
    public static function check(string $html): array
    {
        $hints = [];

        preg_match_all('/<a\b[^>]*>(.*?)<\/a>/is', $html, $links);

        foreach ($links[1] as $inner) {
            $label = mb_strtolower(trim(preg_replace('/[\s\p{P}]+/u', ' ', html_entity_decode(strip_tags($inner), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? ''));

            if (in_array($label, self::VAGUE_LINKS, true)) {
                $hints[] = 'Der Linktext „'.trim(strip_tags($inner)).'“ sagt außerhalb des Satzes nichts. Beschreiben Sie das Ziel, zum Beispiel „Unterlagen zur Mitgliederversammlung“.';
            }
        }

        preg_match_all('/<h([1-6])\b[^>]*>(.*?)<\/h\1>/is', $html, $headings, PREG_SET_ORDER);
        $previous = null;

        foreach ($headings as $heading) {
            $level = (int) $heading[1];

            if (trim(html_entity_decode(strip_tags($heading[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8')) === '') {
                $hints[] = 'Eine Überschrift ist leer.';
            }

            if ($previous !== null && $level > $previous + 1) {
                $hints[] = 'Die Überschriftenebenen springen von '.$previous.' auf '.$level.'. Nehmen Sie keine Ebene aus.';
            }

            $previous = $level;
        }

        if (preg_match('/<img\b(?![^>]*\balt=["\'][^"\']+["\'])[^>]*>/i', $html) === 1) {
            $hints[] = 'Ein Bild hat keinen Alternativtext.';
        }

        preg_match_all('/<p\b[^>]*>(.*?)<\/p>/is', $html, $paragraphs);

        foreach ($paragraphs[1] as $paragraph) {
            $words = preg_split('/\s+/u', trim(strip_tags($paragraph)), -1, PREG_SPLIT_NO_EMPTY) ?: [];

            if (count($words) > self::LONG_PARAGRAPH_WORDS) {
                $hints[] = 'Ein Absatz hat mehr als '.self::LONG_PARAGRAPH_WORDS.' Wörter. Kürzere Absätze lesen sich auf dem Handy besser.';

                break;
            }
        }

        return array_values(array_unique($hints));
    }
}
