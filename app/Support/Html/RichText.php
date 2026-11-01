<?php

namespace App\Support\Html;

use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Support\Str;

/**
 * Formatierter Text aus dem Editor (TinyMCE) sicher speichern und ausgeben.
 *
 * Der Editor liefert HTML. Das wird beim Speichern und beim Ausgeben über eine Positivliste bereinigt: erlaubt sind Absätze, Zeilenumbruch, Überschriften (h2–h4),
 * fett, kursiv, unterstrichen, Listen, Zitate, Trennlinie und Links (http, https, mailto, tel, relative Adressen). Alles andere wird entfernt, Attribute (außer href) auch;
 * Skripte, Formulare, Frames u. Ä. fallen samt Inhalt weg. Ältere Texte ohne Editor (Markdown oder einfacher Text) bleiben unverändert gespeichert und werden beim Ausgeben
 * als Markdown dargestellt (ohne rohes HTML); im Editor erscheinen sie als HTML und werden beim nächsten Speichern umgewandelt.
 */
final class RichText
{
    /** Erlaubte Elemente. */
    private const ALLOWED = ['p', 'br', 'strong', 'em', 'u', 'ul', 'ol', 'li', 'h2', 'h3', 'h4', 'blockquote', 'a', 'hr'];

    /** Ersetzungen: Element => Element der Positivliste. */
    private const RENAME = ['b' => 'strong', 'i' => 'em', 'h1' => 'h2', 'h5' => 'h4', 'h6' => 'h4'];

    /** Diese Elemente verschwinden samt Inhalt. */
    private const DROP = ['script', 'style', 'iframe', 'frame', 'frameset', 'object', 'embed', 'noscript', 'template', 'svg', 'math', 'form', 'textarea', 'select', 'button', 'input', 'head', 'title', 'meta', 'link', 'base', 'applet', 'audio', 'video', 'canvas'];

    private const URL_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    /** Beginnt der Text mit einem Block-Element, stammt er aus dem Editor (sonst gilt er als älterer Markdown- oder Klartext). */
    public static function looksLikeHtml(string $value): bool
    {
        return (bool) preg_match('/^\s*<(p|h[1-6]|ul|ol|blockquote|div|table|hr)(\s|>|\/)/i', $value);
    }

    /** Ist nach Abzug aller Tags und Leerzeichen nichts übrig? (Der Editor liefert leer „<p></p>“.) */
    public static function isEmpty(?string $value): bool
    {
        return self::toPlain((string) $value) === '' && ! preg_match('/<hr\b/i', (string) $value);
    }

    /**
     * Wert zum Speichern: Editor-HTML bereinigt, älterer Text unverändert, leer = null.
     */
    public static function clean(?string $value): ?string
    {
        $value = (string) $value;

        if (self::isEmpty($value)) {
            return null;
        }

        return self::looksLikeHtml($value) ? self::sanitize($value) : trim($value);
    }

    /** Wie clean(), aber für Eingaben vor der Validierung: Leer wird zu einem leeren String, damit „required“ greift. */
    public static function normalize(?string $value): string
    {
        return self::clean($value) ?? '';
    }

    /** Sicheres HTML zur Ausgabe (Editor-HTML bereinigt, älterer Text als Markdown ohne rohes HTML). */
    public static function render(?string $value): string
    {
        $value = (string) $value;

        if (self::isEmpty($value)) {
            return '';
        }

        if (self::looksLikeHtml($value)) {
            return self::sanitize($value);
        }

        return trim(Str::markdown($value, ['html_input' => 'strip', 'allow_unsafe_links' => false, 'renderer' => ['soft_break' => "<br />\n"]]));
    }

    /** Text für den Editor (HTML). */
    public static function forEditor(?string $value): string
    {
        return self::render($value);
    }

    /** Reiner Text ohne Tags, Leerraum zusammengezogen (für Anreißer, Längen, Suche). */
    public static function toPlain(string $value, ?int $limit = null): string
    {
        $html = self::looksLikeHtml($value) || preg_match('/<[a-z][^>]*>/i', $value) ? $value : self::render($value);
        $plain = html_entity_decode(strip_tags(str_replace(['</p>', '<br>', '<br />', '<br/>', '</li>', '</h2>', '</h3>', '</h4>'], ' ', $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = trim((string) preg_replace('/[\s\x{00A0}]+/u', ' ', $plain));

        return $limit === null ? $plain : Str::limit($plain, $limit);
    }

    /** Bereinigt Editor-HTML über die Positivliste. */
    public static function sanitize(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->loadHTML('<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>'.$html.'</body></html>');
        $document->encoding = 'UTF-8';
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $document->getElementsByTagName('body')->item(0);

        if (! $body instanceof DOMElement) {
            return '';
        }

        self::walk($document, $body);

        $out = '';

        foreach ($body->childNodes as $child) {
            $out .= $document->saveHTML($child);
        }

        return trim($out);
    }

    private static function walk(DOMDocument $document, DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if (! $node instanceof DOMElement) {
                if ($node->nodeType !== XML_TEXT_NODE) {
                    $parent->removeChild($node);
                }

                continue;
            }

            $name = strtolower($node->nodeName);

            if (in_array($name, self::DROP, true)) {
                $parent->removeChild($node);

                continue;
            }

            if (isset(self::RENAME[$name])) {
                $replacement = $document->createElement(self::RENAME[$name]);

                while ($node->firstChild !== null) {
                    $replacement->appendChild($node->firstChild);
                }

                $parent->replaceChild($replacement, $node);
                $node = $replacement;
                $name = $replacement->nodeName;
            }

            self::walk($document, $node);

            if (! in_array($name, self::ALLOWED, true)) {
                self::unwrap($parent, $node);

                continue;
            }

            if ($name === 'a') {
                $href = trim((string) $node->getAttribute('href'));

                if (! self::safeUrl($href)) {
                    self::unwrap($parent, $node);

                    continue;
                }

                self::stripAttributes($node);
                $node->setAttribute('href', $href);
                $node->setAttribute('rel', 'noopener noreferrer');

                continue;
            }

            self::stripAttributes($node);
        }
    }

    private static function unwrap(DOMNode $parent, DOMNode $node): void
    {
        while ($node->firstChild !== null) {
            $parent->insertBefore($node->firstChild, $node);
        }

        $parent->removeChild($node);
    }

    private static function stripAttributes(DOMElement $element): void
    {
        foreach (iterator_to_array($element->attributes) as $attribute) {
            $element->removeAttributeNode($attribute);
        }
    }

    private static function safeUrl(string $url): bool
    {
        if ($url === '') {
            return false;
        }

        // Steuer- und Leerzeichen entfernen, damit „java\tscript:“ nicht durchrutscht.
        $compact = strtolower((string) preg_replace('/[\x00-\x20\x7F]+/', '', html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        if (preg_match('/^([a-z][a-z0-9+.\-]*):/', $compact, $match)) {
            return in_array($match[1], self::URL_SCHEMES, true);
        }

        return str_starts_with($compact, '/') || str_starts_with($compact, '#') || ! str_contains($compact, ':');
    }
}
