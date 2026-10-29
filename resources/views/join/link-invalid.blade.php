<x-layouts.app title="Link ungültig" width="narrow">
    <x-vdbs.layout.page-header title="Link ungültig" />

    <x-vdbs.feedback.alert variant="warning" title="{{ $reason }}">
        Links sind zeitlich begrenzt und nur einmal verwendbar. Wenden Sie sich bitte an den Vorstand, damit er Ihnen einen neuen Link sendet.
    </x-vdbs.feedback.alert>

    <p style="margin-top:var(--space-6)">
        <x-vdbs.actions.button :href="route('portal.contact')" icon="mail">Kontakt aufnehmen</x-vdbs.actions.button>
        <x-vdbs.actions.button variant="tertiary" :href="route('home')">Zur Startseite</x-vdbs.actions.button>
    </p>
</x-layouts.app>
