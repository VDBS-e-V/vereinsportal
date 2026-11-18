<?php

namespace App\Modules\Seminars\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Mitteilung zu einem Seminar: Betreff, Anrede, Absätze und ein Link ins Portal. Ob die Person sie abschalten darf, entscheidet der Aufrufer. */
final class SeminarNoticeMail extends Mailable
{
    /** @param  list<string>  $paragraphs */
    public function __construct(public readonly string $subjectLine, public readonly ?string $name, public readonly array $paragraphs, public readonly ?string $url = null, public readonly ?string $urlLabel = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.seminars.notice');
    }
}
