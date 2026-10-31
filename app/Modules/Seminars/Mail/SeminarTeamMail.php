<?php

namespace App\Modules\Seminars\Mail;

use App\Modules\Seminars\Models\Seminar;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Nachricht an Teamende zu einer Bewerbung: bestätigt (mit Schule und Terminen), abgelehnt oder Seminar abgesagt (nur Titel). */
final class SeminarTeamMail extends Mailable
{
    public function __construct(public readonly string $kind, public readonly Seminar $seminar, public readonly string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: match ($this->kind) {
            'confirmed' => 'Ihre Bewerbung wurde bestätigt: '.$this->seminar->title,
            'cancelled' => 'Seminar abgesagt: '.$this->seminar->title,
            default => 'Ihre Bewerbung auf '.$this->seminar->title,
        });
    }

    public function content(): Content
    {
        return new Content(view: 'mail.seminars.team');
    }
}
