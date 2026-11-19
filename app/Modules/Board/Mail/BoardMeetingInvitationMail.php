<?php

namespace App\Modules\Board\Mail;

use App\Modules\Board\Models\BoardMeeting;
use App\Modules\MailBuilder\Mail\TemplatedMail;
use Illuminate\Mail\Mailables\Attachment;

/** Einladung zu einer Vorstandssitzung mit Kalenderdatei im Anhang. Enthält Termin, Ort, Link, Tagesordnung als Text und den Link ins Portal. */
final class BoardMeetingInvitationMail extends TemplatedMail
{
    public function __construct(public readonly BoardMeeting $meeting, public readonly string $url, public readonly string $agenda, public readonly string $note, public readonly string $ics) {}

    /** @return list<Attachment> */
    public function attachments(): array
    {
        return [Attachment::fromData(fn (): string => $this->ics, 'Vorstandssitzung.ics')->withMime('text/calendar')];
    }

    protected function systemKey(): string
    {
        return 'board.invitation';
    }

    protected function values(): array
    {
        return ['titel' => (string) $this->meeting->title, 'datum' => $this->meeting->starts_at->format('d.m.Y'), 'zeit' => $this->meeting->period(), 'ort' => (string) $this->meeting->location, 'video' => (string) $this->meeting->online_link, 'hinweis' => self::paragraphs($this->note), 'tagesordnung' => self::paragraphs($this->agenda), 'url' => $this->url];
    }

    /** Freitext des Vorstands als Absätze (leer, wenn nichts da ist). */
    private static function paragraphs(string $text): string
    {
        return filled($text) ? '<p>'.str_replace(["\r\n", "\r", "\n"], '<br>', e(trim($text))).'</p>' : '';
    }
}
