<?php

namespace App\Modules\Mailings\Mail;

use App\Modules\MailBuilder\Mail\TemplatedMail;
use App\Modules\Mailings\Models\Mailing;

/** Hinweis an den Vorstand: Ein Schreiben liegt zur Freigabe vor (Betreff, Zahl der Empfänger, Link, kein Text). */
final class MailingApprovalMail extends TemplatedMail
{
    public function __construct(public readonly Mailing $mailing, public readonly string $author, public readonly string $url) {}

    protected function systemKey(): string
    {
        return 'mailings.approval';
    }

    protected function values(): array
    {
        return ['autor' => $this->author, 'betreff' => (string) $this->mailing->subject, 'empfaenger' => (string) $this->mailing->recipients_count, 'versand' => $this->mailing->send_at ? $this->mailing->send_at->format('d.m.Y, H:i').' Uhr' : '', 'url' => $this->url];
    }
}
