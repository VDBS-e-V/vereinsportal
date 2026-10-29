<?php

namespace App\Modules\Access\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Bestätigung der beantragten Kontolöschung mit Link zum Abbrechen innerhalb der Wartefrist. */
final class AccountDeletionRequestedMail extends Mailable
{
    public function __construct(public readonly string $cancelUrl, public readonly int $graceDays, public readonly string $until) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Löschung Ihres Kontos im VDBS-Portal beantragt');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.access.account-deletion-requested');
    }
}
