<?php

namespace App\Modules\Tickets\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Models\TicketAttachment;
use App\Modules\Tickets\Support\TicketAttachments;
use App\Modules\Tickets\Support\TicketService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Tickets aus Sicht der anfragenden Person: eigene Tickets, anlegen, im Ticket schreiben, schließen. Das Team sieht dieselbe Seite mit zusätzlichen Möglichkeiten. */
class TicketController extends Controller
{
    public function __construct(private readonly TicketService $tickets, private readonly TicketAttachments $attachments) {}

    public function index(): View
    {
        $tickets = Ticket::query()->where('requester_id', $this->user()->id)->with('assignee.person')->orderByDesc('last_activity_at')->orderByDesc('id')->get();

        return view('support.index', ['tickets' => $tickets]);
    }

    public function create(): View
    {
        return view('support.create', ['categories' => Ticket::CATEGORIES]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'category' => ['required', Rule::in(array_keys(Ticket::CATEGORIES))],
            'message' => ['required', 'string', 'max:10000'],
            'files' => ['nullable', 'array', 'max:'.TicketAttachments::MAX_FILES], 'files.*' => ['file'],
        ], ['subject.required' => 'Bitte geben Sie einen Betreff ein.', 'message.required' => 'Bitte beschreiben Sie Ihr Anliegen.', 'message.max' => 'Die Nachricht darf höchstens 10.000 Zeichen lang sein.']);

        $ticket = $this->tickets->create($this->user(), $data);
        $problems = $this->attachments->addMany($ticket, $ticket->messages()->first(), $request->file('files', []), $this->user());

        $redirect = redirect()->route('support.show', $ticket)->with('success', 'Ihr Ticket '.$ticket->reference().' wurde angelegt. Wir melden uns hier im Ticket bei Ihnen.');

        return $problems === [] ? $redirect : $redirect->withErrors(['files' => implode(' ', $problems)]);
    }

    public function show(Ticket $ticket): View
    {
        $user = $this->user();
        abort_unless(TicketService::canView($user, $ticket), 404);

        $handler = $user->can('tickets.handle');
        $ticket->load(['requester.person', 'assignee.person']);
        $messages = $ticket->messages()->with(['author.person', 'attachments'])->when(! $handler, fn ($query) => $query->where('is_internal', false))->get();
        $events = $ticket->events()->with('actor.person')->get();

        // Nachrichten und Verlauf in einer Zeitleiste
        $timeline = $messages->map(fn ($message): array => ['at' => $message->created_at, 'kind' => 'message', 'item' => $message])
            ->concat($events->map(fn ($event): array => ['at' => $event->created_at, 'kind' => 'event', 'item' => $event]))
            ->sortBy(fn (array $row): int => $row['at']?->getTimestamp() ?? 0)->values();

        return view('support.show', [
            'ticket' => $ticket, 'timeline' => $timeline, 'handler' => $handler, 'isRequester' => $ticket->requester_id === $user->id,
            'handlers' => $handler ? TicketService::handlers() : [], 'statuses' => Ticket::STATUSES, 'priorities' => Ticket::PRIORITIES,
        ]);
    }

    public function message(Request $request, Ticket $ticket): RedirectResponse
    {
        $user = $this->user();
        abort_unless(TicketService::canView($user, $ticket), 404);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:10000'], 'internal' => ['nullable', 'boolean'],
            'files' => ['nullable', 'array', 'max:'.TicketAttachments::MAX_FILES], 'files.*' => ['file'],
        ], ['body.required' => 'Bitte schreiben Sie eine Nachricht.']);
        $internal = $request->boolean('internal') && $user->can('tickets.handle');

        $message = $this->tickets->post($ticket, $user, $data['body'], $internal);
        $problems = $this->attachments->addMany($ticket, $message, $request->file('files', []), $user);

        $redirect = redirect()->to(route('support.show', $ticket).'#verlauf')->with('success', $internal ? 'Die interne Notiz wurde gespeichert.' : 'Ihre Nachricht wurde gesendet.');

        return $problems === [] ? $redirect : $redirect->withErrors(['files' => implode(' ', $problems)]);
    }

    /** Anhang herunterladen: nur für die anfragende Person und das Team; Anhänge interner Notizen nur für das Team. */
    public function download(Ticket $ticket, TicketAttachment $attachment): StreamedResponse
    {
        $user = $this->user();
        abort_unless(TicketService::canView($user, $ticket) && $attachment->ticket_id === $ticket->id, 404);

        if ($attachment->ticket_message_id !== null && ! $user->can('tickets.handle')) {
            abort_if($ticket->messages()->whereKey($attachment->ticket_message_id)->where('is_internal', true)->exists(), 404);
        }

        abort_unless(Storage::disk(TicketAttachment::DISK)->exists($attachment->path), 404);

        return Storage::disk(TicketAttachment::DISK)->download($attachment->path, $attachment->original_name, ['Content-Type' => $attachment->mime, 'X-Content-Type-Options' => 'nosniff']);
    }

    /** Die anfragende Person schließt ihr Ticket (z. B. wenn sich die Frage erledigt hat). */
    public function close(Ticket $ticket): RedirectResponse
    {
        $user = $this->user();
        abort_unless($ticket->requester_id === $user->id, 404);

        $this->tickets->update($ticket, $user, 'closed', null, null, false);

        return redirect()->route('support.show', $ticket)->with('success', 'Das Ticket wurde geschlossen. Schreiben Sie eine Nachricht, wenn Sie es wieder öffnen möchten.');
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
