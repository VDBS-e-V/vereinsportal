@php($crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Mitarbeitende']])
<x-layouts.app title="Mitarbeitende" width="wide">
    <x-vdbs.layout.page-header title="Bereich Mitarbeitende" lead="Willkommen, {{ $name }}. Hier finden Sie Ihre Anträge und den Weg zu den Systemen des Vereins." :breadcrumb="$crumbs" />

    <section aria-labelledby="roles-heading">
        <h2 id="roles-heading" style="font-size:var(--font-size-h3)">Ihre Rollen</h2>
        <ul>
            @foreach ($roles as $role)
                <li>{{ $role }}</li>
            @endforeach
        </ul>
    </section>

    <section aria-labelledby="systems-heading" style="margin-top:var(--space-6)">
        <h2 id="systems-heading" style="font-size:var(--font-size-h3)">Systeme des Vereins</h2>
        @if ($nextcloud !== '')
            <p><x-vdbs.actions.button :href="$nextcloud" icon="external-link" target="_blank" rel="noopener">Nextcloud öffnen</x-vdbs.actions.button></p>
            <p class="vdbs-hint">Sie melden sich dort mit Ihrem Portalkonto an.</p>
        @else
            <p>Der Link zu Nextcloud ist noch nicht hinterlegt. Die Administration trägt ihn unter <code>PORTAL_NEXTCLOUD_URL</code> ein.</p>
        @endif
    </section>

    <section aria-labelledby="forms-heading" style="margin-top:var(--space-6)">
        <h2 id="forms-heading" style="font-size:var(--font-size-h3)">Anträge</h2>
        <p>Anträge an die Geschäftsstelle (zum Beispiel Beschaffungen und Erstattungen) kommen mit dem nächsten Ausbau des Portals hierher.</p>
    </section>
</x-layouts.app>
