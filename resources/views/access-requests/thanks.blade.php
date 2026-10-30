@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Zugang zum Portal', 'href' => route('portal.access')], ['label' => 'Antrag eingegangen']];
@endphp

<x-layouts.app title="Antrag eingegangen" width="medium">
    <x-vdbs.layout.page-header title="Vielen Dank für Ihren Antrag" :breadcrumb="$crumbs" />

    <x-vdbs.feedback.alert variant="success">
        Wir haben Ihren Antrag auf Zugang zum Portal erhalten. Die Geschäftsstelle prüft ihn. Wenn er angenommen wird, bekommen Sie eine E-Mail mit einem Link, über den Sie Ihr Passwort festlegen. Eine Eingangsbestätigung geht an die angegebene Adresse.
    </x-vdbs.feedback.alert>

    <p style="margin-top:var(--space-6)">
        <x-vdbs.actions.button :href="route('home')">Zur Startseite</x-vdbs.actions.button>
        <x-vdbs.actions.button variant="tertiary" :href="route('portal.contact')">Kontakt aufnehmen</x-vdbs.actions.button>
    </p>
</x-layouts.app>
