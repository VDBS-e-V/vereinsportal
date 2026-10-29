@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Konto'], ['label' => 'Sicherheit', 'href' => route('account.security.index')], ['label' => 'E-Mail-Code']];
@endphp

<x-layouts.app title="E-Mail-Code einrichten" width="narrow">
    <x-vdbs.layout.page-header title="E-Mail-Code einrichten" :breadcrumb="$crumbs" />

    <x-vdbs.navigation.stepper label="Einrichtung" :current="1" :steps="['Code bestätigen', 'Wiederherstellungscodes sichern']" />

    <p style="margin-top:var(--space-6)">Wir haben einen sechsstelligen Code an <strong>{{ $email }}</strong> gesendet. Er ist zehn Minuten gültig.</p>

    <x-vdbs.forms.form :action="route('account.security.email.store')">
        <x-vdbs.forms.code name="code" label="Code aus der E-Mail" autofocus />
        <x-slot:actions>
            <x-vdbs.actions.button type="submit">Bestätigen und aktivieren</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="tertiary" :href="route('account.security.index')">Abbrechen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>

    <form method="post" action="{{ route('account.security.email.resend') }}" style="margin-top:var(--space-4)">
        @csrf
        <x-vdbs.actions.button type="submit" variant="quiet" icon="send">Code erneut senden</x-vdbs.actions.button>
        @if ($resendIn > 0)
            <p style="margin:var(--space-1) 0 0;color:var(--text-muted);font-size:var(--font-size-small)">Ein neuer Code ist in {{ $resendIn }} Sekunden möglich.</p>
        @endif
    </form>
</x-layouts.app>
