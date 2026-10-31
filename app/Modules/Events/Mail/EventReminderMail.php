<?php

namespace App\Modules\Events\Mail;

use App\Modules\Events\Models\Event;
use App\Modules\Events\Models\EventRegistration;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Erinnerung am Tag vor der Veranstaltung mit Ticketcode und Abmelde-Link. */
final class EventReminderMail extends Mailable
{
    public function __construct(public readonly Event $event, public readonly EventRegistration $registration, public readonly string $cancelUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Erinnerung: '.$this->event->title.' findet bald statt');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.events.reminder');
    }
}
