<?php

namespace App\Modules\Board\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Board\Models\BoardMeeting;
use App\Modules\Board\Models\BoardMeetingDocument;
use App\Modules\Board\Models\BoardMeetingGuest;
use App\Modules\Board\Services\BoardCalendar;
use App\Modules\Board\Services\BoardInvitations;
use App\Modules\Board\Services\BoardMeetingDocuments;
use App\Support\Html\RichText;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;
use DomainException;

/** Vorstandssitzungen im Portal: planen, Tagesordnung, Anwesenheit und Protokoll. Nur für den Vorstand. */
class BoardMeetingController extends Controller
{
    public function __construct(private readonly BoardCalendar $calendar, private readonly BoardInvitations $invitations, private readonly BoardMeetingDocuments $documents) {}

    public function index(): View
    {
        $feed = $this->calendar->feedUrl();

        return view('board.meetings.index', [
            'upcoming' => BoardMeeting::query()->where('ends_at', '>=', now())->orderBy('starts_at')->get(),
            'past' => BoardMeeting::query()->where('ends_at', '<', now())->orderByDesc('starts_at')->limit(100)->get(),
            'feed' => $feed,
            'webcal' => BoardCalendar::webcal($feed),
            'externalUrl' => $this->calendar->externalUrl(),
            'invitations' => $this->calendar->invitationsEnabled(),
            'recipientCount' => count($this->invitations->recipients()),
        ]);
    }

    public function create(): View
    {
        return view('board.meetings.form', ['meeting' => null, 'members' => $this->members(), 'statuses' => BoardMeeting::STATUSES, 'present' => [], 'excused' => [], 'agenda' => '', 'minutes' => '', 'invitations' => $this->calendar->invitationsEnabled()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $meeting = BoardMeeting::query()->create($this->attributes($data) + ['created_by' => $this->user()->id]);
        $this->syncAttendees($meeting, $data);

        AuditLog::record(AuditEvent::BoardMeetingSaved, $this->user(), null, ['sitzung' => $meeting->title.' am '.$meeting->starts_at->format('d.m.Y')]);

        return $this->afterSave($request, $meeting, 'Die Sitzung wurde angelegt. Im Kalender-Abo erscheint sie automatisch.');
    }

    public function show(BoardMeeting $meeting): View
    {
        $meeting->load(['attendees.person', 'documents.uploadedBy.person', 'guests', 'resolutions', 'tasks.assignees.person']);

        return view('board.meetings.show', [
            'meeting' => $meeting, 'invitations' => $this->calendar->invitationsEnabled(),
            'present' => $meeting->attendees->filter(fn (User $user): bool => $user->pivot->status === 'present'),
            'excused' => $meeting->attendees->filter(fn (User $user): bool => $user->pivot->status === 'excused'),
            'members' => $this->members($meeting), 'outcomes' => \App\Modules\Board\Models\BoardResolution::OUTCOMES,
        ]);
    }

    public function edit(BoardMeeting $meeting): View
    {
        $meeting->load('attendees');

        return view('board.meetings.form', [
            'meeting' => $meeting, 'members' => $this->members($meeting), 'statuses' => BoardMeeting::STATUSES,
            'present' => $meeting->attendees->filter(fn (User $user): bool => $user->pivot->status === 'present')->pluck('id')->all(),
            'excused' => $meeting->attendees->filter(fn (User $user): bool => $user->pivot->status === 'excused')->pluck('id')->all(),
            'agenda' => RichText::forEditor($meeting->agenda), 'minutes' => RichText::forEditor($meeting->minutes), 'invitations' => $this->calendar->invitationsEnabled(),
        ]);
    }

    public function update(Request $request, BoardMeeting $meeting): RedirectResponse
    {
        $data = $this->validated($request);
        $attributes = $this->attributes($data);
        $note = '';

        // Ändert sich das Protokoll nach der Genehmigung, ist es nicht mehr genehmigt.
        if ($meeting->minutes_approved_on !== null && trim((string) $attributes['minutes']) !== trim((string) $meeting->minutes)) {
            $attributes['minutes_approved_on'] = null;
            $note = ' Das Protokoll wurde geändert und ist deshalb wieder nicht genehmigt.';
        }

        $meeting->update($attributes);
        $this->syncAttendees($meeting, $data);

        AuditLog::record(AuditEvent::BoardMeetingSaved, $this->user(), null, ['sitzung' => $meeting->title.' am '.$meeting->starts_at->format('d.m.Y')]);

        return $this->afterSave($request, $meeting, 'Die Sitzung wurde gespeichert.'.$note);
    }

    /** Das Protokoll gilt als genehmigt, meist in der nächsten Sitzung. */
    public function approve(BoardMeeting $meeting): RedirectResponse
    {
        if (! $meeting->hasMinutes()) {
            return back()->with('error', 'Es gibt noch kein Protokoll, das genehmigt werden könnte.');
        }

        $meeting->update(['minutes_approved_on' => today()]);
        AuditLog::record(AuditEvent::BoardMinutesApproved, $this->user(), null, ['sitzung' => $meeting->title.' am '.$meeting->starts_at->format('d.m.Y')]);

        return back()->with('success', 'Das Protokoll ist als genehmigt vermerkt.');
    }

    public function destroy(BoardMeeting $meeting): RedirectResponse
    {
        $this->delete(collect([$meeting]));

        return redirect()->route('board.meetings.index')->with('success', 'Die Sitzung wurde gelöscht.');
    }

    public function destroyMany(Request $request): RedirectResponse
    {
        $ids = $request->validate(['ids' => ['required', 'array', 'min:1'], 'ids.*' => ['integer']], ['ids.required' => 'Bitte wählen Sie mindestens eine Sitzung aus.'])['ids'];
        $meetings = BoardMeeting::query()->whereIn('id', $ids)->get();
        $this->delete($meetings);

        return redirect()->route('board.meetings.index')->with('success', $meetings->count() === 1 ? 'Die Sitzung wurde gelöscht.' : $meetings->count().' Sitzungen wurden gelöscht.');
    }

    public function updateCalendar(Request $request): RedirectResponse
    {
        $data = $request->validate(['calendar_url' => ['nullable', 'string', 'max:500', 'url:https']], ['calendar_url.url' => 'Bitte geben Sie eine vollständige Adresse an, die mit https:// beginnt.']);
        $this->calendar->saveExternalUrl($data['calendar_url'] ?? '', $this->user());

        return redirect()->route('board.meetings.index')->with('success', 'Der Kalenderlink wurde gespeichert.');
    }

    public function renewFeed(): RedirectResponse
    {
        $this->calendar->renewToken($this->user());

        return redirect()->route('board.meetings.index')->with('success', 'Die Abo-Adresse wurde erneuert. Die alte Adresse funktioniert nicht mehr; abonnieren Sie den Kalender mit der neuen Adresse.');
    }

    /** Schaltet die Einladungen per E-Mail ein oder aus. */
    public function updateInvitations(Request $request): RedirectResponse
    {
        $enabled = $request->boolean('enabled');
        $this->calendar->setInvitations($enabled, $this->user());

        return redirect()->route('board.meetings.index')->with('success', $enabled ? 'Einladungen per E-Mail sind eingeschaltet.' : 'Einladungen per E-Mail sind ausgeschaltet.');
    }

    /** Verschickt die Einladung mit Kalenderdatei an die Vorstandsmitglieder. */
    public function invite(Request $request, BoardMeeting $meeting): RedirectResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:2000']]);

        try {
            $result = $this->invitations->send($meeting, $this->user(), $data['note'] ?? null);
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $message = $result['sent'] === 1 ? 'Die Einladung wurde an 1 Vorstandsmitglied gesendet.' : 'Die Einladung wurde an '.$result['sent'].' Vorstandsmitglieder gesendet.';

        return back()->with($result['failed'] > 0 ? 'error' : 'success', $result['failed'] > 0 ? $message.' '.$result['failed'].' E-Mails konnten nicht zugestellt werden.' : $message);
    }

    public function storeGuest(Request $request, BoardMeeting $meeting): RedirectResponse
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:rfc', 'max:190', Rule::unique('board_meeting_guests', 'email')->where('board_meeting_id', $meeting->id)],
            'organization' => ['nullable', 'string', 'max:150'],
        ], ['name.required' => 'Bitte geben Sie den Namen des Gastes an.', 'email.required' => 'Bitte geben Sie die E-Mail-Adresse des Gastes an.', 'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse an.', 'email.unique' => 'Dieser Gast ist schon eingetragen.']);

        if ($meeting->guests()->count() >= 50) {
            return back()->withInput()->withErrors(['email' => 'Eine Sitzung kann höchstens 50 Gäste haben.']);
        }

        $guest = $meeting->guests()->create($data);
        AuditLog::record(AuditEvent::BoardGuestsChanged, $this->user(), null, ['sitzung' => $meeting->title.' am '.$meeting->starts_at->format('d.m.Y'), 'aktion' => 'Gast hinzugefügt', 'gast' => $guest->name]);

        return redirect()->to(route('board.meetings.show', $meeting).'#guests-heading')->with('success', 'Der Gast wurde eingetragen.');
    }

    public function destroyGuests(Request $request, BoardMeeting $meeting): RedirectResponse
    {
        $ids = $request->validate(['ids' => ['required', 'array', 'min:1'], 'ids.*' => ['integer']], ['ids.required' => 'Bitte wählen Sie mindestens einen Gast aus.'])['ids'];
        $guests = $meeting->guests()->whereIn('id', $ids)->get();

        foreach ($guests as $guest) {
            $guest->delete();
        }

        AuditLog::record(AuditEvent::BoardGuestsChanged, $this->user(), null, ['sitzung' => $meeting->title.' am '.$meeting->starts_at->format('d.m.Y'), 'aktion' => 'Gäste entfernt', 'anzahl' => $guests->count()]);

        return redirect()->to(route('board.meetings.show', $meeting).'#guests-heading')->with('success', $guests->count() === 1 ? 'Der Gast wurde entfernt.' : $guests->count().' Gäste wurden entfernt.');
    }

    /** Lädt Gäste per E-Mail mit Kalenderdatei ein. Ohne Auswahl bekommen alle Gäste die Einladung. */
    public function inviteGuests(Request $request, BoardMeeting $meeting): RedirectResponse
    {
        $data = $request->validate(['ids' => ['nullable', 'array'], 'ids.*' => ['integer'], 'note' => ['nullable', 'string', 'max:2000'], 'with_agenda' => ['nullable', 'boolean']]);

        try {
            $result = $this->invitations->sendToGuests($meeting, $this->user(), array_map('intval', $data['ids'] ?? []) ?: null, $data['note'] ?? null, $request->boolean('with_agenda'));
        } catch (DomainException $exception) {
            return redirect()->to(route('board.meetings.show', $meeting).'#guests-heading')->with('error', $exception->getMessage());
        }

        $message = $result['sent'] === 1 ? 'Die Einladung wurde an 1 Gast gesendet.' : 'Die Einladung wurde an '.$result['sent'].' Gäste gesendet.';

        return redirect()->to(route('board.meetings.show', $meeting).'#guests-heading')->with($result['failed'] > 0 ? 'error' : 'success', $result['failed'] > 0 ? $message.' '.$result['failed'].' E-Mails konnten nicht zugestellt werden.' : $message);
    }

    public function storeDocuments(Request $request, BoardMeeting $meeting): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:200'], 'files' => ['required', 'array', 'min:1', 'max:20'], 'files.*' => ['file'],
        ], ['files.required' => 'Bitte wählen Sie mindestens eine Datei.', 'files.max' => 'Bitte laden Sie höchstens 20 Dateien auf einmal hoch.', 'files.*.file' => 'Die Datei konnte nicht gelesen werden.']);

        $done = 0;
        $problems = [];

        foreach ($request->file('files', []) as $file) {
            try {
                $this->documents->add($meeting, $file, $data['title'] ?? null, $this->user());
                $done++;
            } catch (DomainException $exception) {
                $problems[] = $file->getClientOriginalName().': '.$exception->getMessage();
            }
        }

        $redirect = redirect()->to(route('board.meetings.show', $meeting).'#documents-heading');

        if ($problems !== []) {
            $redirect = $redirect->withErrors(['documents' => implode(' ', $problems)]);
        }

        return $done > 0 ? $redirect->with('success', $done === 1 ? 'Der Anhang wurde abgelegt.' : $done.' Anhänge wurden abgelegt.') : $redirect;
    }

    public function destroyDocuments(Request $request, BoardMeeting $meeting): RedirectResponse
    {
        $ids = $request->validate(['ids' => ['required', 'array', 'min:1'], 'ids.*' => ['integer']], ['ids.required' => 'Bitte wählen Sie mindestens einen Anhang aus.'])['ids'];
        $documents = $meeting->documents()->whereIn('id', $ids)->get();

        foreach ($documents as $document) {
            $this->documents->remove($document, $this->user());
        }

        return redirect()->to(route('board.meetings.show', $meeting).'#documents-heading')->with('success', $documents->count() === 1 ? 'Der Anhang wurde gelöscht.' : $documents->count().' Anhänge wurden gelöscht.');
    }

    public function download(BoardMeeting $meeting, BoardMeetingDocument $document): StreamedResponse
    {
        abort_unless($document->board_meeting_id === $meeting->id && Storage::disk(BoardMeetingDocument::DISK)->exists($document->path), 404);

        return Storage::disk(BoardMeetingDocument::DISK)->download($document->path, $document->original_name, ['Content-Type' => $document->mime, 'X-Content-Type-Options' => 'nosniff']);
    }

    /** Nach dem Speichern: auf Wunsch gleich einladen (nur wenn Einladungen eingeschaltet sind). */
    private function afterSave(Request $request, BoardMeeting $meeting, string $message): RedirectResponse
    {
        $redirect = redirect()->route('board.meetings.show', $meeting);

        if (! $request->boolean('send_invitation') || ! $this->calendar->invitationsEnabled()) {
            return $redirect->with('success', $message);
        }

        try {
            $result = $this->invitations->send($meeting, $this->user());
        } catch (DomainException $exception) {
            return $redirect->with('success', $message)->with('error', $exception->getMessage());
        }

        return $redirect->with('success', $message.' Die Einladung ging an '.$result['sent'].' Vorstandsmitglied'.($result['sent'] === 1 ? '' : 'er').'.');
    }

    /** @param  \Illuminate\Support\Collection<int, BoardMeeting>  $meetings */
    private function delete($meetings): void
    {
        foreach ($meetings as $meeting) {
            AuditLog::record(AuditEvent::BoardMeetingDeleted, $this->user(), null, ['sitzung' => $meeting->title.' am '.$meeting->starts_at->format('d.m.Y')]);
            $meeting->delete();
        }
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $request->merge(['agenda' => RichText::normalize($request->input('agenda')), 'minutes' => RichText::normalize($request->input('minutes'))]);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'location' => ['nullable', 'string', 'max:200'],
            'online_link' => ['nullable', 'string', 'max:500', 'url:https'],
            'status' => ['required', Rule::in(array_keys(BoardMeeting::STATUSES))],
            'agenda' => ['nullable', 'string', 'max:60000'],
            'minutes' => ['nullable', 'string', 'max:100000'],
            'send_invitation' => ['nullable', 'boolean'],
            'present' => ['nullable', 'array'], 'present.*' => ['integer'],
            'excused' => ['nullable', 'array'], 'excused.*' => ['integer'],
        ], [
            'title.required' => 'Bitte geben Sie der Sitzung einen Titel.', 'date.required' => 'Bitte wählen Sie das Datum.',
            'start_time.required' => 'Bitte geben Sie den Beginn an.', 'end_time.required' => 'Bitte geben Sie das Ende an.',
            'end_time.after' => 'Das Ende muss nach dem Beginn liegen.', 'online_link.url' => 'Bitte geben Sie eine Adresse an, die mit https:// beginnt.',
        ]);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        return [
            'title' => $data['title'], 'starts_at' => $data['date'].' '.$data['start_time'], 'ends_at' => $data['date'].' '.$data['end_time'],
            'location' => $data['location'] ?? null, 'online_link' => $data['online_link'] ?? null, 'status' => $data['status'],
            'agenda' => RichText::isEmpty($data['agenda'] ?? null) ? null : RichText::clean($data['agenda']),
            'minutes' => RichText::isEmpty($data['minutes'] ?? null) ? null : RichText::clean($data['minutes']),
        ];
    }

    /** @param  array<string, mixed>  $data */
    private function syncAttendees(BoardMeeting $meeting, array $data): void
    {
        $allowed = User::query()->whereIn('id', array_merge($data['present'] ?? [], $data['excused'] ?? []))->pluck('id')->all();
        $sync = [];

        foreach ($data['excused'] ?? [] as $id) {
            if (in_array((int) $id, $allowed, true)) {
                $sync[(int) $id] = ['status' => 'excused'];
            }
        }

        foreach ($data['present'] ?? [] as $id) {
            if (in_array((int) $id, $allowed, true)) {
                $sync[(int) $id] = ['status' => 'present'];
            }
        }

        DB::transaction(fn () => $meeting->attendees()->sync($sync));
    }

    /** Aktive Vorstandsmitglieder, dazu bereits eingetragene Teilnehmende der Sitzung. @return array<int, string> */
    private function members(?BoardMeeting $meeting = null): array
    {
        return app(\App\Modules\Board\Services\BoardMembers::class)->options($meeting ? $meeting->attendees()->pluck('users.id')->all() : []);
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
