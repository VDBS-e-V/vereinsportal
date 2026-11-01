@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Admins'], ['label' => 'System', 'href' => route('system.index')], ['label' => 'Fehlerprotokoll']];
    $total = $entries->total();
    $count = $total === 1 ? '1 Eintrag' : $total.' Einträge';
    $tones = ['emergency' => 'error', 'alert' => 'error', 'critical' => 'error', 'error' => 'error', 'warning' => 'warning', 'notice' => 'neutral', 'info' => 'neutral', 'debug' => 'neutral'];
@endphp

<x-layouts.app title="Fehlerprotokoll" width="wide">
    <x-vdbs.layout.page-header title="Fehlerprotokoll" lead="Das Ende der Anwendungs-Protokolldatei, neueste Einträge zuerst. Passwörter, Schlüssel und Tokens sind geschwärzt. Diese Seite ändert nichts." :breadcrumb="$crumbs" />

    @if ($file === null)
        <x-vdbs.feedback.empty-state title="Keine Protokolldatei" icon="clipboard-list"><p>Im Ordner storage/logs gibt es noch keine Datei. Das ist ein gutes Zeichen: Es ist noch nichts protokolliert worden.</p></x-vdbs.feedback.empty-state>
    @else
        <x-vdbs.data.toolbar label="Fehlerprotokoll filtern" :count="$count" :active="$active" :reset-href="route('system.logs')" search-placeholder="Meldung durchsuchen">
            <x-vdbs.forms.select name="stufe" label="Stufe" placeholder="Alle" :options="$levelOptions" :value="$filters['level']" />
        </x-vdbs.data.toolbar>

        <p style="color:var(--text-muted)">Datei {{ $file }}, {{ number_format($size / 1024, 0, ',', '.') }} KB@if ($truncated), angezeigt wird nur das Ende (die letzten 2 MB)@endif.</p>

        @if ($entries->isEmpty())
            <x-vdbs.feedback.empty-state title="Keine passenden Einträge" icon="clipboard-list"><p>Zu Ihrer Auswahl gibt es keine Einträge.</p></x-vdbs.feedback.empty-state>
        @else
            <x-vdbs.data.table caption="Fehlerprotokoll" :columns="[['key' => 'time', 'label' => 'Zeit'], ['key' => 'level', 'label' => 'Stufe'], ['key' => 'message', 'label' => 'Meldung']]">
                @foreach ($entries as $entry)
                    <tr>
                        <td>{{ $entry['time'] }}</td>
                        <td><x-vdbs.data.badge :tone="$tones[$entry['level']] ?? 'neutral'">{{ $entry['level'] }}</x-vdbs.data.badge></td>
                        <td>
                            {{ $entry['message'] }}
                            @if ($entry['detail'] !== '')
                                <details><summary>Einzelheiten</summary><pre style="white-space:pre-wrap;margin:var(--space-2) 0 0">{{ $entry['detail'] }}</pre></details>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>

            <div style="margin-top:var(--space-4)"><x-vdbs.navigation.pagination :paginator="$entries" /></div>
        @endif
    @endif
</x-layouts.app>
