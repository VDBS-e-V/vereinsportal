<?php

namespace App\Modules\Tasks\Mail;

use App\Modules\Tasks\Models\Task;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Collection;

/** Hinweis an eine Person: Ihr wurden Aufgaben zugewiesen. Nur auf Wunsch der Person (Benachrichtigung „task_assigned“). */
final class TaskAssignedMail extends Mailable
{
    /** @param  Collection<int, Task>  $tasks */
    public function __construct(public readonly Collection $tasks, public readonly string $by, public readonly string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->tasks->count() === 1 ? 'Neue Aufgabe: '.$this->tasks->first()->title : $this->tasks->count().' neue Aufgaben im Vereinsportal');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.tasks.assigned');
    }
}
