@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Konto'], ['label' => 'Sicherheit', 'href' => route('account.security.index')], ['label' => 'Authenticator-App']];
@endphp

<x-layouts.app title="Authenticator-App einrichten" width="medium">
    <x-vdbs.layout.page-header title="Authenticator-App einrichten" :breadcrumb="$crumbs" />

    <x-vdbs.navigation.stepper label="Einrichtung" :current="1" :steps="['App verbinden und Code bestätigen', 'Wiederherstellungscodes sichern']" />

    <x-vdbs.layout.aside-layout variant="panel" size="m" aside-label="Hinweise" style="margin-top:var(--space-6)">
        <h2 style="margin-top:0">1. App verbinden</h2>
        <ol>
            <li>Öffnen Sie Ihre Authenticator-App und wählen Sie „Konto hinzufügen“.</li>
            <li>Wählen Sie die manuelle Eingabe und tragen Sie diesen Schlüssel ein (Typ: zeitbasiert):</li>
        </ol>
        <p><code style="font-size:var(--font-size-h4);letter-spacing:0.08em;overflow-wrap:anywhere">{{ $secret }}</code></p>
        <p>Auf dem Smartphone können Sie die Einrichtung auch <a href="{{ $uri }}">direkt in der App öffnen</a>.</p>

        <h2>2. Code bestätigen</h2>
        <x-vdbs.forms.form :action="route('account.security.totp.store')">
            <x-vdbs.forms.code name="code" label="Aktueller Code aus der App" hint="Sechs Ziffern. Der Code wechselt alle 30 Sekunden." autofocus />
            <x-slot:actions>
                <x-vdbs.actions.button type="submit">Bestätigen und aktivieren</x-vdbs.actions.button>
                <x-vdbs.actions.button variant="tertiary" :href="route('account.security.index')">Abbrechen</x-vdbs.actions.button>
            </x-slot:actions>
        </x-vdbs.forms.form>

        <x-slot:aside>
            <h2 style="margin-top:0;font-size:var(--font-size-h4)">Gut zu wissen</h2>
            <ul>
                <li>Der Schlüssel ist geheim. Teilen Sie ihn mit niemandem.</li>
                <li>Prüfen Sie, dass die Uhr Ihres Geräts automatisch gestellt ist.</li>
                <li>Nach der Bestätigung erhalten Sie Wiederherstellungscodes für den Notfall.</li>
            </ul>
        </x-slot:aside>
    </x-vdbs.layout.aside-layout>
</x-layouts.app>
