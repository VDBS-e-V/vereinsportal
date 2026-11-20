<?php

namespace App\Modules\Board\Mail;

use App\Modules\Board\Models\BoardMeeting;
use App\Modules\Board\Models\BoardMeetingGuest;
use App\Modules\MailBuilder\Mail\TemplatedMail;
use Illuminate\Mail\Mailables\Attachment;

/**
 * Einladung eines externen Gastes zu einer Vorstandssitzung mit Kalenderdatei. Gäste haben keinen Portalzugang, deshalb steht kein Portallink
 * darin; die Tagesordnung steht nur drin, wenn der Vorstand sie ausdrücklich mitsendet.
 */
final class BoardGuestInvitationMail extends TemplatedMail
{
    public function __construct(public readonly BoardMeeting $meeting, public readonly BoardMeetingGuest $guest, public readonly string $club, public readonly string $agenda, public readonly string $note, public readonly string $ics, public readonly string $privacyUrl = '', public readonly int $retentionDays = 30) {}

    /** @return list<Attachment> */
    public function attachments(): array
    {
        return [Attachment::fromData(fn (): string => $this->ics, 'Sitzung.ics')->withMime('text/calendar')];
    }

    protected function systemKey(): string
    {
        return 'board.guest_invitation';
    }

    protected function values(): array
    {
        return ['anrede' => self::greeting($this->guest->name), 'verein' => $this->club, 'titel' => (string) $this->meeting->title, 'datum' => $this->meeting->starts_at->format('d.m.Y'), 'zeit' => $this->meeting->period(), 'ort' => (string) $this->meeting->location, 'video' => (string) $this->meeting->online_link, 'hinweis' => self::paragraphs($this->note), 'tagesordnung' => self::paragraphs($this->agenda), 'aufbewahrung_tage' => (string) $this->retentionDays, 'organisation_hinweis' => filled($this->guest->organization) ? ' sowie Ihre Organisation' : '', 'datenschutz_link' => $this->privacyUrl];
    }

    /** Freitext des Vorstands als Absätze (leer, wenn nichts da ist). */
    private static function paragraphs(string $text): string
    {
        return filled($text) ? '<p>'.str_replace(["\r\n", "\r", "\n"], '<br>', e(trim($text))).'</p>' : '';
    }
}
