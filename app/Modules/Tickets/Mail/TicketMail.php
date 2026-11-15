<?php

namespace App\Modules\Tickets\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Hinweis zu einem Ticket. Enthält bewusst keinen Nachrichtentext, sondern nur den Link ins Portal. */
final class TicketMail extends Mailable
{
    /** @param  'new'|'reply'|'requester_reply'|'assigned'  $kind */
    public function __construct(public readonly string $kind, public readonly string $reference, public readonly string $subjectLine, public readonly string $url, public readonly ?string $by = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: match ($this->kind) {
            'new' => 'Neues Ticket '.$this->reference.': '.$this->subjectLine,
            'assigned' => 'Ticket '.$this->reference.' wurde Ihnen zugewiesen',
            'requester_reply' => 'Neue Nachricht im Ticket '.$this->reference,
            default => 'Antwort auf Ihr Ticket '.$this->reference,
        });
    }

    public function content(): Content
    {
        return new Content(view: 'mail.tickets.notice');
    }
}
