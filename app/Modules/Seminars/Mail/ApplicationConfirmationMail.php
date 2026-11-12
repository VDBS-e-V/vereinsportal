<?php

namespace App\Modules\Seminars\Mail;

use App\Modules\Seminars\Models\Seminar;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Bestätigung der E-Mail-Adresse nach einer Bewerbung über den allgemeinen Bewerbungslink. */
final class ApplicationConfirmationMail extends Mailable
{
    public function __construct(public readonly Seminar $seminar, public readonly string $applicantName, public readonly string $confirmUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Bitte bestätigen Sie Ihre Bewerbung: '.$this->seminar->title);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.seminars.application-confirmation');
    }
}
