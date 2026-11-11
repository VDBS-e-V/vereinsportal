<?php

namespace App\Modules\Board\Mail;

use App\Modules\Board\Models\BoardMeeting;
use App\Modules\Board\Models\BoardMeetingGuest;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Einladung eines externen Gastes zu einer Vorstandssitzung mit Kalenderdatei. Gäste haben keinen Portalzugang, deshalb steht kein Portallink
 * darin; die Tagesordnung steht nur drin, wenn der Vorstand sie ausdrücklich mitsendet.
 */
final class BoardGuestInvitationMail extends Mailable
{
    public function __construct(public readonly BoardMeeting $meeting, public readonly BoardMeetingGuest $guest, public readonly string $club, public readonly string $agenda, public readonly string $note, public readonly string $ics, public readonly string $privacyUrl = '', public readonly int $retentionDays = 30) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Einladung: '.$this->meeting->title.' am '.$this->meeting->starts_at->format('d.m.Y'));
    }

    public function content(): Content
    {
        return new Content(view: 'mail.board.guest-invitation');
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        return [Attachment::fromData(fn (): string => $this->ics, 'Sitzung.ics')->withMime('text/calendar')];
    }
}
