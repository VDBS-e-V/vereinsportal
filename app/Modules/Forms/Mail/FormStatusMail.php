<?php

namespace App\Modules\Forms\Mail;

use App\Modules\MailBuilder\Mail\TemplatedMail;

/** Benachrichtigung zu einem Antrag. Enthält nur Nummer, Art und einen kurzen Text – keine Beträge und keine Belege. */
final class FormStatusMail extends TemplatedMail
{
    public function __construct(public readonly string $number, public readonly string $typeLabel, public readonly string $mailSubject, public readonly string $text) {}

    protected function systemKey(): string
    {
        return 'forms.status';
    }

    protected function values(): array
    {
        return ['betreff' => $this->mailSubject, 'nummer' => $this->number, 'art' => $this->typeLabel, 'text' => $this->text];
    }
}
