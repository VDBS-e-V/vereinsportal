<?php

namespace App\Modules\Events\Mail;

use App\Modules\Events\Models\Event;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Bestätigungslink zur Anmeldung (Double-Opt-in). */
final class EventConfirmMail extends Mailable
{
    public function __construct(public readonly Event $event, public readonly string $name, public readonly string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Bitte bestätigen Sie Ihre Anmeldung: '.$this->event->title);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.events.confirm', with: ['hours' => \App\Modules\Events\Models\Event::PENDING_HOURS]);
    }
}
