@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Admins'], ['label' => 'System']];
    $tones = ['ok' => 'success', 'warning' => 'warning', 'error' => 'error', 'info' => 'neutral'];
    $labels = ['ok' => 'In Ordnung', 'warning' => 'Prüfen', 'error' => 'Fehler', 'info' => 'Info'];
    $errors = $counts['error'] ?? 0;
    $warnings = $counts['warning'] ?? 0;
@endphp

<x-layouts.app title="System" width="wide">
    <x-vdbs.layout.page-header title="System" lead="Zustand des Portals auf einen Blick. Diese Seite ändert nichts; Aufgaben finden Sie unter „Aufgaben“." :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button :href="route('system.tasks')" icon="settings">Aufgaben</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    <x-vdbs.layout.grid :columns="4">
        <x-vdbs.data.stat label="Prüfungen" :value="$errors > 0 ? $errors.' mit Fehler' : ($warnings > 0 ? $warnings.' zu prüfen' : 'Alles in Ordnung')" :tone="$errors > 0 ? 'error' : ($warnings > 0 ? 'warning' : 'success')" icon="shield" />
        <x-vdbs.data.stat label="Migrationen" :value="$pending === 0 ? 'Aktuell' : ($pending < 0 ? 'Unbekannt' : $pending.' ausstehend')" :tone="$pending === 0 ? 'success' : 'warning'" icon="clipboard-list" />
        <x-vdbs.data.stat label="Planer" :value="$schedulerAge === null ? 'Noch nie gelaufen' : 'Vor '.$schedulerAge.' Min.'" :tone="$schedulerAge !== null && $schedulerAge <= 10 ? 'success' : 'warning'" icon="clock" />
        <x-vdbs.data.stat label="Wartungsmodus" :value="$maintenance ? 'Eingeschaltet' : 'Aus'" :tone="$maintenance ? 'warning' : 'success'" icon="settings" />
    </x-vdbs.layout.grid>

    <section aria-labelledby="checks-title" style="margin-top:var(--space-8)">
        <h2 id="checks-title" style="font-size:var(--font-size-h3)">Prüfungen</h2>
        <x-vdbs.data.table caption="Prüfungen" :columns="[['key' => 'group', 'label' => 'Bereich'], ['key' => 'label', 'label' => 'Prüfung'], ['key' => 'status', 'label' => 'Ergebnis'], ['key' => 'detail', 'label' => 'Hinweis']]">
            @foreach ($checks as $check)
                <tr>
                    <td>{{ $check['group'] }}</td>
                    <td>{{ $check['label'] }}</td>
                    <td><x-vdbs.data.badge :tone="$tones[$check['status']] ?? 'neutral'">{{ $labels[$check['status']] ?? $check['status'] }}</x-vdbs.data.badge></td>
                    <td>{{ $check['detail'] }}</td>
                </tr>
            @endforeach
        </x-vdbs.data.table>
    </section>
</x-layouts.app>
