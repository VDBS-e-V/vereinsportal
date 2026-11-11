<?php

namespace App\Modules\Seminars\Mail;

use App\Modules\Seminars\Models\Seminar;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Nachricht an Teamende nach der Teamzusammenstellung: im Team (mit Schule und Terminen), nicht berücksichtigt oder Seminar abgesagt (nur Titel). */
final class SeminarTeamMail extends Mailable
{
    public function __construct(public readonly string $kind, public readonly Seminar $seminar, public readonly string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: match ($this->kind) {
            'confirmed' => 'Sie sind im Team: '.$this->seminar->title,
            'cancelled' => 'Seminar abgesagt: '.$this->seminar->title,
            default => 'Ihr Interesse an '.$this->seminar->title,
        });
    }

    public function content(): Content
    {
        return new Content(view: 'mail.seminars.team');
    }
}
