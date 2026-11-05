<?php

namespace App\Modules\Feedback\Mail;

use App\Modules\Seminars\Models\Seminar;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Erinnerung an die Rückmeldung zu einem Seminar. Enthält nur Seminar, Gruppe und Link – keine Zahlen. */
final class FeedbackReminderMail extends Mailable
{
    public function __construct(public readonly Seminar $seminar, public readonly string $audience, public readonly string $url, public readonly ?string $closesOn) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Erinnerung: Rückmeldung zum Seminar '.($this->seminar->number ? $this->seminar->number.' ' : '').$this->seminar->title);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.feedback.reminder');
    }
}
