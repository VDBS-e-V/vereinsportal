<?php

namespace App\Modules\Access\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** An die ALTE Adresse: Hinweis, dass eine Änderung beantragt wurde (Schutz vor Missbrauch). */
final class EmailChangeNoticeMail extends Mailable
{
    public function __construct(
        public readonly string $newEmail,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Änderung Ihrer E-Mail-Adresse im VDBS-Portal beantragt');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.access.email-change-notice');
    }
}
