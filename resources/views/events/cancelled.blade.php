<x-layouts.app title="Abgemeldet" width="narrow">
    <h1>Du bist abgemeldet</h1>
    <p>Deine Anmeldung für <strong>{{ $title }}</strong> und deine Angaben sind gelöscht. Danke für die Rückmeldung!</p>
    <p><x-vdbs.actions.button variant="secondary" :href="route('events.public.index')">Zu den Veranstaltungen</x-vdbs.actions.button></p>
</x-layouts.app>
