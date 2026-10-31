<?php

namespace App\Modules\Events\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Events\Enums\EventStatus;
use App\Modules\Events\Models\Event;
use App\Modules\Events\Models\EventRegistration;
use App\Modules\Events\Services\EventRegistrations;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** Öffentliche Seiten der Großveranstaltungen (Bereich Start): Liste, Anmeldung, Bestätigung der E-Mail-Adresse und Teilnahmebestätigung. Entwürfe sieht nur, wer Veranstaltungen ansehen darf. */
class PublicEventController extends Controller
{
    public function __construct(private readonly EventRegistrations $registrations) {}

    public function index(): View
    {
        return view('events.index', ['events' => Event::query()->where('status', '!=', EventStatus::Draft->value)->with('sessions')->get()->sortBy(fn (Event $event): string => (string) $event->sessions->first()?->starts_at)->values()]);
    }

    public function show(string $slug): View
    {
        $event = $this->find($slug);

        return view('events.show', ['event' => $event, 'preview' => ! $event->status->isPublic()]);
    }

    public function register(Request $request, string $slug): RedirectResponse
    {
        $event = $this->find($slug);
        $data = $request->validate(EventRegistrations::rules() + ['consent' => ['accepted']], EventRegistrations::messages() + ['consent.accepted' => 'Bitte stimme der Speicherung deiner Daten zu.']);

        try {
            $this->registrations->register($event, $data);
        } catch (DomainException $exception) {
            return redirect()->route('events.public.show', $event->slug)->withInput()->withErrors(['registration' => $exception->getMessage()]);
        }

        // Immer dieselbe Antwort, auch bei bekannten Anmeldungen.
        return redirect()->route('events.public.show', $event->slug)->with('success', 'Fast geschafft: Wir haben dir eine E-Mail geschickt. Bitte bestätige darin deine Anmeldung.');
    }

    public function confirm(EventRegistration $registration): View
    {
        $confirmed = $this->registrations->confirm($registration);

        return view('events.confirmed', ['registration' => $confirmed ? $registration->fresh('event') : null]);
    }

    public function certificateForm(): View
    {
        return view('events.certificate-form');
    }

    public function certificate(Request $request): View|RedirectResponse
    {
        $data = $request->validate(['ticket' => ['required', 'string', 'max:20'], 'email' => ['required', 'email', 'max:254']], [
            'ticket.required' => 'Bitte gib deinen Ticketcode ein.',
            'email.required' => 'Bitte gib deine E-Mail-Adresse ein.',
            'email.email' => 'Bitte gib eine gültige E-Mail-Adresse ein.',
        ]);

        $registration = $this->registrations->forCertificate($data['ticket'], $data['email']);

        if ($registration === null) {
            return redirect()->route('events.public.certificate')->withInput($request->only('email'))->withErrors(['ticket' => 'Dazu haben wir keine Teilnahme gefunden. Bitte prüfe Ticketcode und E-Mail-Adresse. Die Bestätigung gibt es erst, wenn deine Anwesenheit eingetragen ist.']);
        }

        return view('events.certificate', ['registration' => $registration, 'back' => route('events.public.certificate')]);
    }

    private function find(string $slug): Event
    {
        $event = Event::query()->where('slug', $slug)->with('sessions')->firstOrFail();

        abort_unless($event->status->isPublic() || Gate::allows('events.view'), 404);

        return $event;
    }
}
