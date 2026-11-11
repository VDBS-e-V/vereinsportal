@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Admins'], ['label' => 'System', 'href' => route('system.index')], ['label' => 'Warteschlange']];
@endphp

<x-layouts.app title="Warteschlange" width="wide">
    <x-vdbs.layout.page-header title="Warteschlange" lead="Aufträge, die im Hintergrund laufen sollten und fehlgeschlagen sind. Sie können jeden Auftrag erneut versuchen oder verwerfen." :breadcrumb="$crumbs" />

    <x-vdbs.forms.error-summary />

    <x-vdbs.layout.grid :columns="3">
        <x-vdbs.data.stat label="Treiber" :value="$driver" icon="settings" />
        <x-vdbs.data.stat label="Wartend" :value="$waiting === null ? 'Unbekannt' : $waiting" :tone="($waiting ?? 0) > 0 ? 'warning' : 'success'" icon="clock" />
        <x-vdbs.data.stat label="Fehlgeschlagen" :value="$available ? count($failed) : 'Unbekannt'" :tone="count($failed) > 0 ? 'error' : 'success'" icon="shield" />
    </x-vdbs.layout.grid>

    @if (! $available)
        <x-vdbs.feedback.alert variant="warning">Die Tabelle für fehlgeschlagene Aufträge gibt es nicht. Hier lässt sich nichts anzeigen.</x-vdbs.feedback.alert>
    @elseif ($failed === [])
        <div style="margin-top:var(--space-6)"><x-vdbs.feedback.empty-state title="Keine fehlgeschlagenen Aufträge" icon="clipboard-list"><p>Alles in Ordnung: Es ist nichts liegen geblieben.</p></x-vdbs.feedback.empty-state></div>
    @else
        <section aria-labelledby="failed-title" style="margin-top:var(--space-6)">
            <div style="display:flex;flex-wrap:wrap;gap:var(--space-3);align-items:center;justify-content:space-between">
                <h2 id="failed-title" style="font-size:var(--font-size-h3);margin:0">Fehlgeschlagene Aufträge</h2>
                <x-vdbs.actions.button variant="danger" size="s" data-vdbs-dialog-open="flush-failed">Alle verwerfen …</x-vdbs.actions.button>
            </div>

            <x-vdbs.data.table caption="Fehlgeschlagene Aufträge" :columns="[['key' => 'job', 'label' => 'Auftrag'], ['key' => 'queue', 'label' => 'Warteschlange'], ['key' => 'at', 'label' => 'Fehlgeschlagen'], ['key' => 'message', 'label' => 'Fehler'], ['key' => 'action', 'label' => 'Aktion']]">
                @foreach ($failed as $job)
                    <tr>
                        <td>{{ $job['job'] }}</td>
                        <td>{{ $job['queue'] }}</td>
                        <td>{{ $job['failed_at'] }}</td>
                        <td>
                            {{ $job['message'] }}
                            @if ($job['detail'] !== '')
                                <details><summary>Einzelheiten</summary><pre style="white-space:pre-wrap;margin:var(--space-2) 0 0">{{ $job['detail'] }}</pre></details>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex;gap:var(--space-2);flex-wrap:wrap">
                                <form method="post" action="{{ route('system.queue.retry', $job['uuid']) }}">@csrf<x-vdbs.actions.button type="submit" variant="secondary" size="s">Erneut versuchen</x-vdbs.actions.button></form>
                                <form method="post" action="{{ route('system.queue.forget', $job['uuid']) }}">@csrf @method('delete')<x-vdbs.actions.button type="submit" variant="danger-text" size="s">Verwerfen</x-vdbs.actions.button></form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
        </section>

        <x-vdbs.overlays.confirm-dialog id="flush-failed" title="Alle fehlgeschlagenen Aufträge verwerfen?" :action="route('system.queue.flush')" method="delete" variant="danger" confirm-label="Alle verwerfen">
            <p>Die Aufträge werden endgültig aus der Liste gelöscht und nicht mehr ausgeführt.</p>
            <x-slot:fields>
                <x-vdbs.forms.password id="password-flush" name="password" label="Zur Bestätigung Ihr Passwort" :required="true" />
            </x-slot:fields>
        </x-vdbs.overlays.confirm-dialog>
    @endif
</x-layouts.app>
