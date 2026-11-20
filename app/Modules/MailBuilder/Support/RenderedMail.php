<?php

namespace App\Modules\MailBuilder\Support;

/** Ergebnis des Renderns: Betreff, HTML, Textfassung und Hinweise zur Prüfung. */
final readonly class RenderedMail
{
    /** @param  list<array{level: string, message: string}>  $issues */
    public function __construct(public string $subject, public string $html, public string $text, public array $issues = []) {}
}
