<?php

namespace App\Modules\Board\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Board\Models\BoardMeeting;
use App\Modules\Board\Services\BoardCalendar;
use App\Support\Html\RichText;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Vorstandssitzungen im Portal: planen, Tagesordnung, Anwesenheit und Protokoll. Nur für den Vorstand. */
class BoardMeetingController extends Controller
{
    public function __construct(private readonly BoardCalendar $calendar) {}

    public function index(): View
    {
        $feed = $this->calendar->feedUrl();

        return view('board.meetings.index', [
            'upcoming' => BoardMeeting::query()->where('ends_at', '>=', now())->orderBy('starts_at')->get(),
            'past' => BoardMeeting::query()->where('ends_at', '<', now())->orderByDesc('starts_at')->limit(100)->get(),
            'feed' => $feed,
            'webcal' => BoardCalendar::webcal($feed),
            'externalUrl' => $this->calendar->externalUrl(),
        ]);
    }

    public function create(): View
    {
        return view('board.meetings.form', ['meeting' => null, 'members' => $this->members(), 'statuses' => BoardMeeting::STATUSES, 'present' => [], 'excused' => [], 'agenda' => '', 'minutes' => '']);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $meeting = BoardMeeting::query()->create($this->attributes($data) + ['created_by' => $this->user()->id]);
        $this->syncAttendees($meeting, $data);

        AuditLog::record(AuditEvent::BoardMeetingSaved, $this->user(), null, ['sitzung' => $meeting->title.' am '.$meeting->starts_at->format('d.m.Y')]);

        return redirect()->route('board.meetings.show', $meeting)->with('success', 'Die Sitzung wurde angelegt. Im Kalender-Abo erscheint sie automatisch.');
    }

    public function show(BoardMeeting $meeting): View
    {
        $meeting->load('attendees.person');

        return view('board.meetings.show', [
            'meeting' => $meeting,
            'present' => $meeting->attendees->filter(fn (User $user): bool => $user->pivot->status === 'present'),
            'excused' => $meeting->attendees->filter(fn (User $user): bool => $user->pivot->status === 'excused'),
        ]);
    }

    public function edit(BoardMeeting $meeting): View
    {
        $meeting->load('attendees');

        return view('board.meetings.form', [
            'meeting' => $meeting, 'members' => $this->members($meeting), 'statuses' => BoardMeeting::STATUSES,
            'present' => $meeting->attendees->filter(fn (User $user): bool => $user->pivot->status === 'present')->pluck('id')->all(),
            'excused' => $meeting->attendees->filter(fn (User $user): bool => $user->pivot->status === 'excused')->pluck('id')->all(),
            'agenda' => RichText::forEditor($meeting->agenda), 'minutes' => RichText::forEditor($meeting->minutes),
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

        return redirect()->route('board.meetings.show', $meeting)->with('success', 'Die Sitzung wurde gespeichert.'.$note);
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
        $users = User::query()->with('person')
            ->where(fn ($query) => $query
                ->where(fn ($active) => $active->where('status', UserStatus::Active->value)->whereHas('activeRoleAssignments', fn ($assignments) => $assignments->whereHas('role', fn ($role) => $role->where('key', RoleKey::BoardMember->value))))
                ->when($meeting, fn ($or) => $or->orWhereIn('id', $meeting->attendees()->pluck('users.id'))))
            ->get();

        return $users->mapWithKeys(fn (User $user): array => [$user->id => BoardMeeting::attendeeName($user)])->sort()->all();
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
