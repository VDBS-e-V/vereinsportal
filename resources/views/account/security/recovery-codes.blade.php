@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Konto'], ['label' => 'Sicherheit', 'href' => route('account.security.index')], ['label' => 'Wiederherstellungscodes']];
@endphp

<x-layouts.app title="Wiederherstellungscodes" width="narrow">
    <x-vdbs.layout.page-header title="Wiederherstellungscodes" :breadcrumb="$crumbs" />

    <x-vdbs.navigation.stepper label="Einrichtung" :current="2" :steps="['App verbinden und Code bestätigen', 'Wiederherstellungscodes sichern']" />

    <x-vdbs.feedback.alert variant="warning" title="Diese Codes sehen Sie nur jetzt">
        Speichern oder drucken Sie sie an einem sicheren Ort, zum Beispiel in einem Passwortmanager. Nach dem Verlassen dieser Seite sind sie nicht mehr abrufbar.
    </x-vdbs.feedback.alert>

    <x-vdbs.data.code-list :codes="$codes" label="Ihre Wiederherstellungscodes" />

    <p>Jeder Code funktioniert genau einmal, wenn Ihre App oder Ihr Postfach nicht erreichbar ist.</p>

    <x-vdbs.actions.button-group>
        <x-vdbs.actions.button type="button" variant="secondary" icon="printer" onclick="window.print()">Drucken</x-vdbs.actions.button>
        <x-vdbs.actions.button :href="route('account.security.index')">Ich habe die Codes gesichert</x-vdbs.actions.button>
    </x-vdbs.actions.button-group>
</x-layouts.app>
