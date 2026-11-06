<?php

namespace App\Modules\Contracts\Mail;

use App\Modules\Seminars\Models\Seminar;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Erinnerung an die Koordination: Für Verträge eines vergangenen Seminars fehlt der unterschriebene Scan. Nur Seminar und Zahl, keine Namen. */
final class ContractScanReminderMail extends Mailable
{
    public function __construct(public readonly Seminar $seminar, public readonly int $open, public readonly string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Unterschriebene Verträge fehlen: '.$this->seminar->title);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.contracts.scan-reminder');
    }
}
