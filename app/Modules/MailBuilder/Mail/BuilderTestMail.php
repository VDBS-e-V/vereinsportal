<?php

namespace App\Modules\MailBuilder\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Symfony\Component\Mime\Email;

/** Testmail aus dem Mail-Builder: schickt den gezeigten Stand mit Beispielwerten an die eigene Adresse. */
final class BuilderTestMail extends Mailable
{
    public function __construct(private readonly string $subjectLine, private readonly string $htmlBody, private readonly string $plainText) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[Test] '.$this->subjectLine);
    }

    public function content(): Content
    {
        $text = $this->plainText;
        $this->withSymfonyMessage(function (Email $message) use ($text): void {
            $message->text($text);
        });

        return new Content(htmlString: $this->htmlBody);
    }
}
