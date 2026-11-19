<?php

namespace App\Modules\ContactForms\Mail;

use App\Modules\MailBuilder\Mail\TemplatedMail;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;

/** Eingang aus einem Kontaktformular an die zuständige Stelle (Antworten gehen an die absendende Person) oder, auf Wunsch, die Kopie an die absendende Person. */
final class ContactFormMail extends TemplatedMail
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
        return new Envelope(
            replyTo: $this->copy ? [] : [new Address($this->person['email'], $this->senderName())],
            subject: $this->rendered()->subject,
        );
    }

    protected function systemKey(): string
    {
        return 'contact_form.mail';
    }

    protected function values(): array
    {
        $title = filled($this->subjectLine) ? (string) $this->subjectLine : $this->formName.(filled($this->topicTitle) ? ' – '.$this->topicTitle : '');

        return [
            'praefix' => $this->copy ? 'Ihre Kopie: ' : '[Kontaktformular] ',
            'titel' => $title,
            'kopie' => $this->copy ? '1' : '',
            'vorname' => $this->person['first_name'],
            'empfaenger' => $this->recipientLabel,
            'formular' => $this->formName,
            'thema' => (string) $this->topicTitle,
            'von' => $this->senderName().' ('.$this->person['email'].')',
            'telefon' => (string) ($this->person['phone'] ?? ''),
            'organisation' => (string) ($this->person['organization'] ?? ''),
            'betreff' => (string) $this->subjectLine,
            'nachricht' => filled($this->body) ? '<p>'.str_replace(["\r\n", "\r", "\n"], '<br>', e(trim((string) $this->body))).'</p>' : '',
            'antworten' => array_map(fn (array $answer): string => '<strong>'.e($answer['label']).':</strong> '.str_replace(["\r\n", "\r", "\n"], '<br>', e($answer['value'])), $this->answers),
        ];
    }
}
