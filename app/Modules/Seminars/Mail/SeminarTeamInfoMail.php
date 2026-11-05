<?php

namespace App\Modules\Seminars\Mail;

use App\Modules\Seminars\Models\Seminar;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Gemeinsame Information an das Team eines Seminars, mit Terminen, Ort und Team-Übersicht. */
final class SeminarTeamInfoMail extends Mailable
{
    /** @param  list<array{name: string, email: ?string, phone: ?string}>  $team */
    public function __construct(public readonly Seminar $seminar, public readonly string $greetingName, public readonly array $team, public readonly ?string $extraText, public readonly string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Ihr Team für '.($this->seminar->number ? $this->seminar->number.' ' : '').$this->seminar->title);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.seminars.team-info');
    }
}
