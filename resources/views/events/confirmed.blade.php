<x-layouts.app title="Anmeldung bestätigt" width="narrow">
    @if ($registration)
        <h1>Deine Anmeldung ist bestätigt</h1>
        <p>Danke, {{ $registration->name }}! Du bist für <strong>{{ $registration->event->title }}</strong> angemeldet.</p>
        <p>Dein Ticketcode: <strong style="font-size:var(--font-size-h3);letter-spacing:0.1em">{{ $registration->ticket() }}</strong></p>
        <p>Wir haben dir die Anmeldebestätigung auch per E-Mail geschickt. Halte den Ticketcode am Veranstaltungstag bereit.</p>
        <p><x-vdbs.actions.button variant="secondary" :href="route('events.public.index')">Zu den Veranstaltungen</x-vdbs.actions.button></p>
    @else
        <h1>Der Link ist nicht mehr gültig</h1>
        <p>Die Anmeldung ist abgelaufen oder die Veranstaltung ist inzwischen ausgebucht. Bitte melde dich bei Bedarf noch einmal an.</p>
        <p><x-vdbs.actions.button :href="route('events.public.index')">Zu den Veranstaltungen</x-vdbs.actions.button></p>
    @endif
</x-layouts.app>
