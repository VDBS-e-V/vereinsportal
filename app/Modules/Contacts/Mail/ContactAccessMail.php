<?php

namespace App\Modules\Contacts\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;

/** Der persönliche Zugang (Link und Code) für eine Adresse der Mailingliste. */
final class ContactAccessMail extends Mailable
{
    public function __construct(public readonly string $greetingName, public readonly string $accessLink, public readonly string $accessCode, public readonly string $codeHint, public readonly string $unsubscribeUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Ihr persönlicher Zugang für Seminarbewerbungen');
    }

    public function headers(): Headers
    {
        return new Headers(text: ['List-Unsubscribe' => '<'.$this->unsubscribeUrl.'>', 'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click']);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.contacts.access');
    }
}
