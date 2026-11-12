<?php

namespace App\Modules\Events\Mail;

use App\Modules\Events\Models\Event;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Mitteilung der Koordination an Angemeldete. Der Text kommt aus dem Editor und wird beim Ausgeben bereinigt (App\Support\Html\RichText). */
final class EventMessageMail extends Mailable
{
    public function __construct(public readonly Event $event, public readonly string $subjectLine, public readonly string $body, public readonly string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.events.message');
    }
}
