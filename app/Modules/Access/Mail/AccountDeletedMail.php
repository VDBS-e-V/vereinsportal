<?php

namespace App\Modules\Access\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Letzte Nachricht an die alte Adresse: Das Konto wurde gelöscht (anonymisiert). */
final class AccountDeletedMail extends Mailable
{
    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Ihr Konto im VDBS-Portal wurde gelöscht');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.access.account-deleted');
    }
}
