<?php

namespace App\Http\Controllers;

use App\Modules\Access\Models\User;
use App\Modules\Events\Enums\EventStatus;
use App\Modules\Events\Models\Event;
use App\Modules\Events\Models\EventRegistration;
use App\Modules\Events\Services\EventRegistrations;
use App\Modules\Partners\Models\PartnerUser;
use App\Modules\Partners\Services\SchoolAccounts;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Bereich Schulen: Konten mit der Rolle Schule sehen nur Seminare und Kostenvoranschläge der eigenen Schule, können ein Seminar anfragen,
 * Termin und Kostenvoranschlag bestätigen und der Koordination eine Rückmeldung schicken. Alles andere bleibt der Koordination vorbehalten.
 */
class SchoolAreaController extends Controller
{
    public function __construct(private readonly SchoolAccounts $accounts) {}

    public function home(Request $request): View
    {
        return view('school.home', ['account' => $this->account($request)]);
    }

    public function events(Request $request): View
    {
        $account = $this->account($request);

        return view('school.events', [
            'account' => $account,
            'events' => Event::query()->where('status', '!=', EventStatus::Draft->value)->with('sessions')
                ->withCount(['registrations as own_count' => fn ($query) => $query->where('partner_id', $account->partner_id)])->get()
                ->sortBy(fn (Event $event): string => (string) $event->sessions->first()?->starts_at)->values(),
        ]);
    }

    public function event(Request $request, Event $event): View
    {
        $account = $this->account($request);
        abort_unless($event->status->isPublic(), 404);

        return view('school.event', [
            'account' => $account,
            'event' => $event->load('sessions'),
            'open' => $event->status === EventStatus::Open && ! $event->deadlinePassed(),
            'registrations' => $event->registrations()->where('partner_id', $account->partner_id)->get(),
            'maxRows' => EventRegistrations::MAX_ROWS,
        ]);
    }

    public function eventTemplate(Request $request, Event $event): StreamedResponse
    {
        $this->account($request);

        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            foreach (EventRegistrations::template(false) as $row) {
                fputcsv($out, $row, ';', '"', '\\');
            }

            fclose($out);
        }, 'anmeldungen-vorlage.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function eventImport(Request $request, Event $event): RedirectResponse
    {
        $account = $this->account($request);
        abort_unless($event->status->isPublic(), 404);

        if ($event->status !== EventStatus::Open || $event->deadlinePassed()) {
            return redirect()->route('school.events.show', $event)->withErrors(['school' => 'Für diese Veranstaltung nimmt das Portal keine Listen mehr an.']);
        }

        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']], ['file.required' => 'Bitte wählen Sie eine CSV-Datei.', 'file.mimes' => 'Erlaubt sind CSV-Dateien.', 'file.max' => 'Die Datei darf höchstens 2 MB groß sein.']);

        $result = app(EventRegistrations::class)->import($event, (string) file_get_contents((string) $request->file('file')?->getRealPath()), $account->user, $request->boolean('send_mails'), $account->partner);

        if ($result['errors'] !== []) {
            return redirect()->route('school.events.show', $event)->with('import_errors', array_slice($result['errors'], 0, 50))->with('import_error_count', count($result['errors']));
        }

        return redirect()->route('school.events.show', $event)->with('success', $result['created'].' Schülerinnen und Schüler sind angemeldet'.($request->boolean('send_mails') ? ', die Anmeldebestätigungen sind unterwegs.' : '.'));
    }

    public function eventRegistrationDestroy(Request $request, Event $event, EventRegistration $registration): RedirectResponse
    {
        $account = $this->account($request);

        abort_unless($registration->event_id === $event->id && $registration->partner_id === $account->partner_id, 404);

        app(EventRegistrations::class)->remove($registration, $account->user);

        return redirect()->route('school.events.show', $event)->with('success', 'Die Anmeldung wurde entfernt.');
    }

    private function account(Request $request): PartnerUser
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        $account = $this->accounts->forUser($user);

        abort_if($account === null, 403, 'Ihr Konto ist keiner Schule zugeordnet.');

        return $account;
    }
}
