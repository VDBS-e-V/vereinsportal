<?php

namespace App\Modules\Board\Mail;

use App\Modules\Board\Models\BoardMeeting;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Einladung zu einer Vorstandssitzung mit Kalenderdatei im Anhang. Enthält Termin, Ort, Link, Tagesordnung als Text und den Link ins Portal. */
final class BoardMeetingInvitationMail extends Mailable
{
    public function __construct(public readonly BoardMeeting $meeting, public readonly string $url, public readonly string $agenda, public readonly string $note, public readonly string $ics) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Einladung: '.$this->meeting->title.' am '.$this->meeting->starts_at->format('d.m.Y'));
    }

    public function content(): Content
    {
        return new Content(view: 'mail.board.invitation');
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        return [Attachment::fromData(fn (): string => $this->ics, 'Vorstandssitzung.ics')->withMime('text/calendar')];
    }
}
