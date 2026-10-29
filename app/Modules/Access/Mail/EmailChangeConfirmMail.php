<?php

namespace App\Modules\Access\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** An die NEUE Adresse: Link zur Bestätigung der E-Mail-Änderung. */
final class EmailChangeConfirmMail extends Mailable
{
    public function __construct(
        public readonly string $url,
        public readonly string $newEmail,
        public readonly int $validHours,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Neue E-Mail-Adresse für das VDBS-Portal bestätigen');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.access.email-change-confirm');
    }
}
