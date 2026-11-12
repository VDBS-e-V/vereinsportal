@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Verteiler']];
@endphp

<x-layouts.app title="Verteiler" width="wide">
    <x-vdbs.layout.page-header title="Verteiler" lead="Gespeicherte Empfängerkreise für Rundschreiben. Die Zahl wird bei jedem Öffnen neu berechnet." :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button :href="route('mailings.segments.create')" icon="plus">Neuer Verteiler</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    @if ($rows->isEmpty())
        <x-vdbs.feedback.empty-state title="Noch keine Verteiler" icon="users">
            <p>Legen Sie zum Beispiel einen Verteiler „Teamende Berlin/Brandenburg ohne Einarbeitung“ an.</p>
            <x-slot:actions><x-vdbs.actions.button :href="route('mailings.segments.create')">Neuer Verteiler</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.feedback.empty-state>
    @else
        <form method="post" action="{{ route('mailings.segments.destroy-many') }}">
            @csrf
            <x-vdbs.data.table caption="Verteiler" :columns="[['key' => 'sel', 'label' => 'Auswahl'], ['key' => 'name', 'label' => 'Name'], ['key' => 'count', 'label' => 'Erreichbar jetzt'], ['key' => 'visible', 'label' => 'Nutzbar von']]">
                @foreach ($rows as $row)
                    @php $segment = $row['segment']; @endphp
                    <tr class="vdbs-table__row--link">
                        <td><input type="checkbox" name="ids[]" value="{{ $segment->id }}" aria-label="{{ $segment->name }} auswählen"></td>
                        <td><a class="vdbs-table__row-link" href="{{ route('mailings.segments.edit', $segment) }}">{{ $segment->name }}</a>@if (filled($segment->description))<br><small>{{ $segment->description }}</small>@endif</td>
                        <td>{{ $row['count'] }}@if ($row['count'] === 0) <x-vdbs.data.badge tone="warning">Niemand</x-vdbs.data.badge>@endif</td>
                        <td>{{ collect((array) $segment->visible_to)->map(fn ($key) => \App\Modules\Mailings\Models\MailSegment::AREAS[$key] ?? $key)->implode(', ') }}</td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
            <x-vdbs.actions.button type="submit" variant="danger-text" size="s" onclick="return confirm('Die ausgewählten Verteiler löschen?')">Ausgewählte löschen</x-vdbs.actions.button>
        </form>
    @endif
</x-layouts.app>
