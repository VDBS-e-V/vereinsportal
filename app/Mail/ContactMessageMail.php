<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Nachricht aus dem öffentlichen Kontaktformular an den gewählten Empfänger; Antworten gehen an die absendende Person. */
final class ContactMessageMail extends Mailable
{
    public function __construct(
        public readonly string $senderName,
        public readonly string $senderEmail,
        public readonly string $recipientLabel,
        public readonly string $topic,
        public readonly string $body,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->senderEmail, $this->senderName)],
            subject: '[Kontaktformular] '.$this->topic,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.contact-message');
    }
}
