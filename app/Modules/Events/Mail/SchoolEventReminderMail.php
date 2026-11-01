<?php

namespace App\Modules\Events\Mail;

use App\Modules\Events\Models\Event;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Erinnerung an ein Schulkonto kurz vor dem Anmeldeschluss einer Großveranstaltung (mit Zahl der bisher angemeldeten Schülerinnen und Schüler der Schule). */
final class SchoolEventReminderMail extends Mailable
{
    public function __construct(public readonly Event $event, public readonly string $name, public readonly int $ownCount, public readonly string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Anmeldeschluss naht: '.$this->event->title);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.events.school-reminder');
    }
}
