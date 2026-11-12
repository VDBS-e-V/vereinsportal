<?php

namespace App\Modules\Tasks\Mail;

use App\Modules\Tasks\Models\Task;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Collection;

/** Erinnerung an bald fällige und überfällige Aufgaben, eine Mail je Person und Lauf. Nur auf Wunsch der Person (Benachrichtigung „task_due“). */
final class TaskDueMail extends Mailable
{
    /**
     * @param  Collection<int, Task>  $soon
     * @param  Collection<int, Task>  $overdue
     */
    public function __construct(public readonly Collection $soon, public readonly Collection $overdue, public readonly string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Erinnerung: Aufgaben im VDBS-Portal sind fällig');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.tasks.due');
    }
}
