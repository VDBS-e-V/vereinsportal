<x-layouts.app title="Abmelden" width="narrow">
    <h1>Von der Veranstaltung abmelden?</h1>
    <p>{{ $registration->name }}, du bist für <strong>{{ $registration->event->title }}</strong> {{ $registration->isWaitlisted() ? 'auf der Warteliste' : 'angemeldet' }}. Wenn du dich abmeldest, werden deine Angaben gelöscht und dein Platz wird frei. Das lässt sich nicht rückgängig machen.</p>

    <form method="post" action="{{ $action }}">
        @csrf
        <x-vdbs.actions.button type="submit" variant="danger" icon="trash-2">Ja, abmelden</x-vdbs.actions.button>
        <x-vdbs.actions.button variant="text" :href="route('events.public.index')">Nein, ich bleibe angemeldet</x-vdbs.actions.button>
    </form>
</x-layouts.app>
