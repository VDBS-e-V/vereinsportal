<?php

namespace App\Modules\Seminars\Mail;

use App\Modules\Seminars\Models\Seminar;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Hinweis an die Koordination: Der Stichtag für Interessenbekundungen ist vorbei, die Interessenbekundungen können gesichtet werden. Ohne Namen. */
final class SeminarDeadlineMail extends Mailable
{
    public function __construct(public readonly Seminar $seminar, public readonly int $interested, public readonly string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Stichtag erreicht: Team für '.$this->seminar->title.' zusammenstellen');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.seminars.deadline');
    }
}
