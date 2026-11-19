<?php

namespace App\Modules\Tickets\Mail;

use App\Modules\MailBuilder\Mail\TemplatedMail;

/** Hinweis zu einem Ticket. Enthält bewusst keinen Nachrichtentext, sondern nur den Link ins Portal. */
final class TicketMail extends TemplatedMail
{
    /** @param  'new'|'reply'|'requester_reply'|'assigned'  $kind */
    public function __construct(public readonly string $kind, public readonly string $reference, public readonly string $subjectLine, public readonly string $url, public readonly ?string $by = null) {}

    protected function systemKey(): string
    {
        return match ($this->kind) {
            'new' => 'tickets.new',
            'assigned' => 'tickets.assigned',
            'requester_reply' => 'tickets.requester_reply',
            default => 'tickets.reply',
        };
    }

    protected function values(): array
    {
        return [
            'nummer' => $this->reference,
            'betreff' => $this->subjectLine,
            'von' => filled($this->by) ? (string) $this->by : ($this->kind === 'assigned' ? 'Das Team' : 'Die anfragende Person'),
            'von_zusatz' => filled($this->by) ? ' von '.$this->by : '',
            'url' => $this->url,
        ];
    }
}
