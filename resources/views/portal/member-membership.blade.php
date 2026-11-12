@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Mitglieder'], ['label' => 'Mitgliedschaft']];
    $state = $membership?->effectiveStatus();
@endphp

<x-layouts.app title="Mitgliedschaft" width="medium">
    <x-vdbs.layout.page-header title="Ihre Mitgliedschaft" lead="Status, Beitritt und Austritt. Die Mitgliedschaft gehört zum Verein, Ihr Portalkonto ist davon getrennt." :breadcrumb="$crumbs" />

    <section aria-labelledby="membership-heading">
        <h2 id="membership-heading" style="font-size:var(--font-size-h3)">Mitgliedschaft</h2>

        @if ($membership)
            <x-vdbs.data.detail-list layout="inline">
                <x-vdbs.data.detail-item label="Status">{{ $state->label() }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Antrag vom">{{ $membership->requested_on?->format('d.m.Y') }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Mitglied seit">{{ $membership->starts_on?->format('d.m.Y') }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Letzter Tag der Mitgliedschaft">{{ $membership->ends_on?->format('d.m.Y') }}</x-vdbs.data.detail-item>
            </x-vdbs.data.detail-list>
        @else
            <p>Zu Ihrem Konto ist keine Mitgliedschaft hinterlegt.</p>
        @endif
    </section>

    <section aria-labelledby="contact-heading" style="margin-top:var(--space-8)">
        <h2 id="contact-heading" style="font-size:var(--font-size-h3)">Fragen und Austritt</h2>
        <p>Bei Fragen zur Mitgliedschaft, zu Ihren Daten oder zu einem Austritt wenden Sie sich bitte an den Vorstand. Einen Austritt teilen Sie dem Vorstand mit, wie es die Satzung vorsieht.</p>
        <p><x-vdbs.actions.button :href="route('portal.contact')" icon="mail">Vorstand kontaktieren</x-vdbs.actions.button></p>
    </section>

    <p style="margin-top:var(--space-8)">Ihr Portalkonto (Anmeldung, Sicherheit, Stammdaten) verwalten Sie unter <a href="{{ route('account.home') }}">Konto</a>.</p>
</x-layouts.app>
