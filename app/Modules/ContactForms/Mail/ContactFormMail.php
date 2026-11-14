<?php

namespace App\Modules\ContactForms\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Eingang aus einem Kontaktformular an die zuständige Stelle (Antworten gehen an die absendende Person) oder, auf Wunsch, die Kopie an die absendende Person. */
final class ContactFormMail extends Mailable
{
    /**
     * @param  array{first_name: string, last_name: string, email: string, phone: ?string, organization: ?string}  $person
     * @param  list<array{label: string, value: string}>  $answers
     */
    public function __construct(
        public readonly string $formName,
        public readonly string $recipientLabel,
        public readonly ?string $topicTitle,
        public readonly array $person,
        public readonly ?string $subjectLine,
        public readonly ?string $body,
        public readonly array $answers,
        public readonly bool $copy = false,
    ) {}

    public function senderName(): string
    {
        return trim($this->person['first_name'].' '.$this->person['last_name']);
    }

    public function envelope(): Envelope
    {
        $title = filled($this->subjectLine) ? $this->subjectLine : $this->formName.(filled($this->topicTitle) ? ' – '.$this->topicTitle : '');

        return new Envelope(
            replyTo: $this->copy ? [] : [new Address($this->person['email'], $this->senderName())],
            subject: ($this->copy ? 'Ihre Kopie: ' : '[Kontaktformular] ').$title,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.contact-form');
    }
}
