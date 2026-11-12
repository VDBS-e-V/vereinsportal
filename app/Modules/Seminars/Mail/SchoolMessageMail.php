<?php

namespace App\Modules\Seminars\Mail;

use App\Modules\Seminars\Models\Seminar;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Hinweis an die Koordination über eine Nachricht aus dem Schulbereich (neue Anfrage, Bestätigung, Rückmeldung). Der Text steht im Portal, nicht in der Mail. */
final class SchoolMessageMail extends Mailable
{
    public function __construct(public readonly string $kind, public readonly Seminar $seminar, public readonly string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: match ($this->kind) {
            'request' => 'Neue Seminaranfrage: '.$this->seminar->partner->name,
            'confirmed' => 'Schule hat bestätigt: '.$this->seminar->title,
            default => 'Rückmeldung der Schule: '.$this->seminar->title,
        });
    }

    public function content(): Content
    {
        return new Content(view: 'mail.seminars.school');
    }
}
