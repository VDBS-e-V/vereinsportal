<?php

namespace App\Modules\Seminars\Mail;

use App\Modules\MailBuilder\Mail\TemplatedMail;

/** Mitteilung zu einem Seminar: Betreff, Anrede, Absätze und ein Link ins Portal. Ob die Person sie abschalten darf, entscheidet der Aufrufer. */
final class SeminarNoticeMail extends TemplatedMail
{
    /** @param  list<string>  $paragraphs */
    public function __construct(public readonly string $subjectLine, public readonly ?string $name, public readonly array $paragraphs, public readonly ?string $url = null, public readonly ?string $urlLabel = null) {}

    protected function systemKey(): string
    {
        return 'seminars.notice';
    }

    protected function values(): array
    {
        return ['betreff' => $this->subjectLine, 'anrede' => self::greeting($this->name), 'absaetze' => implode('', array_map(fn (string $paragraph): string => '<p>'.e($paragraph).'</p>', $this->paragraphs)), 'link' => (string) $this->url, 'link_text' => filled($this->urlLabel) ? (string) $this->urlLabel : 'Im Vereinsportal öffnen'];
    }
}
