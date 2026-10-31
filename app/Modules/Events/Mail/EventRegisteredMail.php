<?php

namespace App\Modules\Events\Mail;

use App\Modules\Events\Models\Event;
use App\Modules\Events\Models\EventRegistration;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Automatische Anmeldebestätigung mit Ticketcode, Terminen und Hinweis auf die Teilnahmebestätigung. */
final class EventRegisteredMail extends Mailable
{
    public function __construct(public readonly Event $event, public readonly EventRegistration $registration, public readonly string $certificateUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Ihre Anmeldung: '.$this->event->title);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.events.registered');
    }
}
