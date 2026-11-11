<?php

namespace App\Modules\Events\Mail;

use App\Modules\Events\Models\Event;
use App\Modules\Events\Models\EventRegistration;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Bestätigung der Anmeldung auf der Warteliste (ohne Ticketcode, der kommt erst beim Nachrücken). */
final class EventWaitlistedMail extends Mailable
{
    public function __construct(public readonly Event $event, public readonly EventRegistration $registration, public readonly string $cancelUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Du stehst auf der Warteliste: '.$this->event->title);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.events.waitlisted');
    }
}
