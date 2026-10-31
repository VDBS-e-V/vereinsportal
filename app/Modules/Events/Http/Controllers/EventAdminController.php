<?php

namespace App\Modules\Events\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Events\Enums\EventStatus;
use App\Modules\Events\Models\Event;
use App\Modules\Events\Models\EventRegistration;
use App\Modules\Events\Models\EventSession;
use App\Modules\Events\Services\EventAdministration;
use App\Modules\Events\Services\EventRegistrations;
use App\Support\CsvCell;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Großveranstaltungen in der Koordination: Liste, Anlegen, Termine, Anmeldungen (Liste, Import, Export), Anwesenheit und Teilnahmebestätigung. */
class EventAdminController extends Controller
{
    private const PER_PAGE = 20;

    public function __construct(private readonly EventAdministration $events, private readonly EventRegistrations $registrations) {}

    public function index(Request $request): View
    {
        $status = EventStatus::tryFrom((string) $request->query('status', ''));

        $events = Event::query()->when($status !== null, fn ($query) => $query->where('status', $status?->value))->withCount(['registrations', 'registrations as confirmed_count' => fn ($query) => $query->whereNotNull('verified_at')])->with('sessions')
            ->orderByDesc('updated_at')->orderByDesc('id')->paginate(self::PER_PAGE)->withQueryString();

        return view('events.admin-index', ['events' => $events, 'statusOptions' => EventStatus::options(), 'status' => $status?->value, 'canManage' => Gate::allows('events.manage')]);
    }

    public function show(Request $request, Event $event): View
    {
        $search = trim((string) $request->query('q', ''));
        $like = '%'.addcslashes($search, '%_\\').'%';

        $registrations = $event->registrations()->withCount('attendances')
            ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner->where('name', 'like', $like)->orWhere('school', 'like', $like)->orWhere('class_name', 'like', $like)->orWhere('ticket_code', strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $search) ?? ''))))
            ->paginate(50)->withQueryString();

        return view('events.admin-show', [
            'event' => $event->load('sessions'),
            'registrations' => $registrations,
            'search' => $search,
            'confirmed' => $event->confirmedCount(),
            'total' => $event->registrations()->count(),
            'canManage' => Gate::allows('events.manage'),
        ]);
    }

    public function create(): View
    {
        return view('events.admin-form', ['event' => null, 'values' => ['status' => EventStatus::Draft->value]]);
    }

    public function store(Request $request): RedirectResponse
    {
        $event = $this->events->create($this->validated($request), $this->actor($request));

        return redirect()->route('events.admin.show', $event)->with('success', 'Die Veranstaltung wurde angelegt. Tragen Sie jetzt die Termine ein.');
    }

    public function edit(Event $event): View
    {
        return view('events.admin-form', ['event' => $event, 'values' => $event->only(['title', 'description', 'location', 'capacity']) + ['status' => $event->status->value, 'registration_deadline' => $event->registration_deadline?->format('Y-m-d\TH:i')]]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $this->events->update($event, $this->validated($request), $this->actor($request));

        return redirect()->route('events.admin.show', $event)->with('success', 'Die Änderungen wurden gespeichert.');
    }

    public function destroy(Request $request, Event $event): RedirectResponse
    {
        try {
            $this->events->delete($event, $this->actor($request));
        } catch (DomainException $exception) {
            return redirect()->route('events.admin.show', $event)->withErrors(['event' => $exception->getMessage()]);
        }

        return redirect()->route('events.admin.index')->with('success', 'Die Veranstaltung wurde gelöscht.');
    }

    public function storeSession(Request $request, Event $event): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'], 'start' => ['required', 'date_format:H:i'], 'end' => ['required', 'date_format:H:i', 'after:start'],
            'location' => ['nullable', 'string', 'max:200'], 'notes' => ['nullable', 'string', 'max:300'],
        ], ['date.required' => 'Bitte geben Sie ein Datum an.', 'start.required' => 'Bitte geben Sie den Beginn an.', 'end.required' => 'Bitte geben Sie das Ende an.', 'end.after' => 'Das Ende muss nach dem Beginn liegen.']);

        $this->events->addSession($event, $data);

        return redirect()->route('events.admin.show', $event)->with('success', 'Der Termin wurde eingetragen.');
    }

    public function destroySession(Event $event, EventSession $session): RedirectResponse
    {
        abort_unless($session->event_id === $event->id, 404);

        $session->delete();

        return redirect()->route('events.admin.show', $event)->with('success', 'Der Termin wurde entfernt.');
    }

    public function importForm(Event $event): View
    {
        return view('events.admin-import', ['event' => $event]);
    }

    public function importTemplate(Event $event): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            foreach (EventRegistrations::template() as $row) {
                fputcsv($out, $row, ';', '"', '\\');
            }

            fclose($out);
        }, 'anmeldungen-vorlage.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function import(Request $request, Event $event): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']], ['file.required' => 'Bitte wählen Sie eine CSV-Datei.', 'file.mimes' => 'Erlaubt sind CSV-Dateien.', 'file.max' => 'Die Datei darf höchstens 2 MB groß sein.']);

        $result = $this->registrations->import($event, (string) file_get_contents((string) $request->file('file')?->getRealPath()), $this->actor($request), $request->boolean('send_mails'));

        if ($result['errors'] !== []) {
            return redirect()->route('events.admin.import', $event)->with('import_errors', array_slice($result['errors'], 0, 50))->with('import_error_count', count($result['errors']));
        }

        return redirect()->route('events.admin.show', $event)->with('success', "{$result['created']} Anmeldungen importiert".($request->boolean('send_mails') ? ', die Anmeldebestätigungen sind unterwegs.' : '.'));
    }

    public function destroyRegistration(Request $request, Event $event, EventRegistration $registration): RedirectResponse
    {
        abort_unless($registration->event_id === $event->id, 404);

        $this->registrations->remove($registration, $this->actor($request));

        return redirect()->route('events.admin.show', $event)->with('success', 'Die Anmeldung wurde entfernt.');
    }

    public function export(Event $event): StreamedResponse
    {
        $sessions = $event->sessions;

        return response()->streamDownload(function () use ($event, $sessions): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_merge(['Name', 'Schule', 'Klasse', 'Geburtsdatum', 'E-Mail', 'Ticketcode', 'Bestätigt', 'Quelle'], $sessions->map(fn (EventSession $session): string => 'Anwesend '.$session->starts_at->format('d.m.Y'))->all()), ';', '"', '\\');

            foreach ($event->registrations()->with('attendances')->get() as $registration) {
                fputcsv($out, array_map([CsvCell::class, 'safe'], array_merge([
                    $registration->name, $registration->school, $registration->class_name, $registration->birth_date->format('d.m.Y'), $registration->email, $registration->ticket(),
                    $registration->isConfirmed() ? 'ja' : 'nein', $registration->source === EventRegistration::SOURCE_UPLOAD ? 'Liste' : 'Selbstanmeldung',
                ], $sessions->map(fn (EventSession $session): string => $registration->attendances->contains('session_id', $session->id) ? 'ja' : '')->all())), ';', '"', '\\');
            }

            fclose($out);
        }, 'anmeldungen-'.$event->slug.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function attendance(Request $request, Event $event, EventSession $session): View
    {
        abort_unless($session->event_id === $event->id, 404);

        $search = trim((string) $request->query('q', ''));
        $like = '%'.addcslashes($search, '%_\\').'%';

        return view('events.admin-attendance', [
            'event' => $event,
            'session' => $session,
            'search' => $search,
            'registrations' => $event->registrations()->whereNotNull('verified_at')
                ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner->where('name', 'like', $like)->orWhere('school', 'like', $like)->orWhere('class_name', 'like', $like)))
                ->get(),
            'present' => $session->attendances()->pluck('registration_id')->flip(),
            'presentCount' => $session->attendances()->count(),
            'canManage' => Gate::allows('events.manage'),
        ]);
    }

    public function mark(Request $request, Event $event, EventSession $session): RedirectResponse
    {
        abort_unless($session->event_id === $event->id, 404);

        $data = $request->validate(['registration' => ['required', 'integer'], 'present' => ['required', 'boolean'], 'q' => ['nullable', 'string', 'max:100']]);
        $registration = $event->registrations()->whereNotNull('verified_at')->findOrFail($data['registration']);

        $this->registrations->mark($session, $registration, (bool) $data['present'], $this->actor($request));

        return redirect()->route('events.admin.attendance', ['event' => $event, 'session' => $session, 'q' => $data['q'] ?? null]);
    }

    public function markTicket(Request $request, Event $event, EventSession $session): RedirectResponse
    {
        abort_unless($session->event_id === $event->id, 404);

        $data = $request->validate(['ticket' => ['required', 'string', 'max:20']], ['ticket.required' => 'Bitte geben Sie einen Ticketcode ein.']);
        $registration = $this->registrations->markByTicket($event, $session, $data['ticket'], $this->actor($request));

        return redirect()->route('events.admin.attendance', ['event' => $event, 'session' => $session])->with($registration ? 'success' : 'ticket_unknown', $registration ? $registration->name.' ist als anwesend eingetragen.' : 'Der Ticketcode ist bei dieser Veranstaltung nicht bekannt.');
    }

    public function certificate(Event $event, EventRegistration $registration): View
    {
        abort_unless($registration->event_id === $event->id, 404);

        return view('events.certificate', ['registration' => $registration->load(['event', 'attendances.session']), 'back' => route('events.admin.show', $event)]);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:200'],
            'status' => ['required', Rule::in(array_keys(EventStatus::options()))],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'registration_deadline' => ['nullable', 'date'],
        ], [
            'title.required' => 'Bitte geben Sie einen Titel ein.',
            'capacity.integer' => 'Bitte geben Sie eine ganze Zahl ein.',
            'capacity.min' => 'Es muss mindestens ein Platz geben.',
            'registration_deadline.date' => 'Bitte geben Sie ein gültiges Datum ein.',
        ]);
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();

        abort_unless($actor instanceof User, 403);

        return $actor;
    }
}
