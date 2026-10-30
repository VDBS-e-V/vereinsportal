<?php

namespace App\Modules\AccessRequests\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Neutrale Antwort, wenn es zur Adresse schon ein Konto oder einen offenen Antrag gibt. Verrät nichts, was Fremde nicht wissen dürfen. */
final class AccessRequestNoticeMail extends Mailable
{
    public function __construct(public readonly string $loginUrl, public readonly string $resetUrl, public readonly string $contactUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Ihr Antrag auf Zugang zum VDBS-Portal');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.access-requests.notice');
    }
}
