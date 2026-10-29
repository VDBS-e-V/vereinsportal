@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Verwaltung'], ['label' => 'Protokoll']];
    $total = $entries->total();
    $count = $total === 1 ? '1 Eintrag' : $total.' Einträge';
    $columns = [
        ['key' => 'time', 'label' => 'Zeit'],
        ['key' => 'event', 'label' => 'Ereignis'],
        ['key' => 'actor', 'label' => 'Akteur'],
        ['key' => 'subject', 'label' => 'Betroffen'],
        ['key' => 'ip', 'label' => 'IP-Adresse'],
    ];
@endphp

<x-layouts.app title="Protokoll" width="wide">
    <x-vdbs.layout.page-header title="Protokoll" lead="Wer hat wann was getan? Einträge sind unveränderlich und enthalten nie Passwörter oder Codes." :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button variant="secondary" :href="$exportUrl" icon="download">Als CSV exportieren</x-vdbs.actions.button>
        </x-slot:actions>
        <x-slot:tabs><x-admin.nav current="audit" /></x-slot:tabs>
    </x-vdbs.layout.page-header>

    <x-vdbs.data.toolbar label="Protokoll filtern" :count="$count" :active="$active" :reset-href="route('admin.audit.index')" search-placeholder="Akteur oder Betroffene suchen">
        <x-vdbs.forms.select name="category" label="Gruppe" placeholder="Alle" :options="$categoryOptions" :value="$filters['category']" />
        <x-vdbs.forms.select name="event" label="Ereignis" placeholder="Alle" :options="$eventOptions" :value="$filters['event']" />
        <x-vdbs.forms.input name="from" label="Von" type="date" :value="$filters['from']" />
        <x-vdbs.forms.input name="to" label="Bis" type="date" :value="$filters['to']" />
        @if ($filters['subject'])
            <input type="hidden" name="subject" value="{{ $filters['subject'] }}">
        @endif
    </x-vdbs.data.toolbar>

    @if ($entries->isEmpty())
        <x-vdbs.feedback.empty-state title="Keine Einträge gefunden" icon="search">
            <p>Zu den gewählten Filtern gibt es keine Einträge. Prüfen Sie den Zeitraum oder entfernen Sie Filter.</p>
            <x-slot:actions><x-vdbs.actions.button variant="secondary" :href="route('admin.audit.index')">Filter zurücksetzen</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.feedback.empty-state>
    @else
        <x-vdbs.data.table caption="Protokolleinträge" :columns="$columns">
            @foreach ($entries as $entry)
                @php $event = $entry->eventType; @endphp
                <tr class="vdbs-table__row--link">
                    <td><a class="vdbs-table__row-link" href="{{ route('admin.audit.show', $entry) }}"><time datetime="{{ $entry->occurred_at->toIso8601String() }}">{{ $entry->occurred_at->format('d.m.Y, H:i:s') }}</time></a></td>
                    <td><x-vdbs.data.badge :tone="$event?->tone() ?? 'neutral'">{{ $event?->label() ?? $entry->event }}</x-vdbs.data.badge></td>
                    <td>{{ $entry->actor_label ?? '–' }}</td>
                    <td>{{ $entry->subject_label ?? '–' }}</td>
                    <td>{{ $entry->ip_address ?? '–' }}</td>
                </tr>
            @endforeach
        </x-vdbs.data.table>

        <div style="margin-top:var(--space-4)"><x-vdbs.navigation.pagination :paginator="$entries" /></div>
    @endif
</x-layouts.app>
