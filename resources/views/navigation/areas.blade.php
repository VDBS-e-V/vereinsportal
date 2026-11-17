@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Admins'], ['label' => 'Navigation']];
    $tab = 'areas';
    $count = $areas->count();
@endphp

<x-layouts.app title="Navigation" width="wide">
    <x-vdbs.layout.page-header title="Übersicht der Bereiche" lead="Reihenfolge, Name und Sichtbarkeit der Bereiche im Kopfmenü. Das Ausblenden betrifft nur die Menüs: Wer eine Adresse kennt und die Rolle dafür hat, erreicht die Seite weiterhin." :breadcrumb="$crumbs" />

    <nav class="vdbs-task-filter" aria-label="Web-Control">
        <x-vdbs.actions.button :href="route('navigation.edit')" variant="{{ $tab === 'areas' ? 'primary' : 'secondary' }}" icon="layout-dashboard">Bereiche</x-vdbs.actions.button>
        <x-vdbs.actions.button :href="route('navigation.headers')" variant="{{ $tab === 'headers' ? 'primary' : 'secondary' }}" icon="menu">Header</x-vdbs.actions.button>
    </nav>

    <form method="get" action="{{ route('navigation.edit') }}" class="vdbs-task-filter" aria-label="Bereiche suchen">
        <x-vdbs.forms.input name="q" label="Suchbegriff" type="search" :optional="true" maxlength="60" :value="$q" />
        <x-vdbs.actions.button type="submit" variant="secondary" icon="search">Suchen</x-vdbs.actions.button>
        @if ($q !== '')<x-vdbs.actions.button :href="route('navigation.edit')" variant="secondary" icon="x">Zurücksetzen</x-vdbs.actions.button>@endif
    </form>

    @if ($areas->isEmpty())
        <x-vdbs.feedback.empty-state title="Kein Bereich gefunden" icon="search"><p>Zum Suchbegriff gibt es keinen Bereich.</p></x-vdbs.feedback.empty-state>
    @else
        <x-vdbs.data.table caption="Bereiche des Portals" :columns="[['key' => 'sort', 'label' => 'Reihenfolge'], ['key' => 'name', 'label' => 'Name'], ['key' => 'path', 'label' => 'Adresse'], ['key' => 'header', 'label' => 'Kopfzeile'], ['key' => 'lists', 'label' => 'Listen'], ['key' => 'act', 'label' => 'Bearbeiten']]">
            @foreach ($areas as $area)
                @php $row = \App\Modules\NavigationControl\Models\NavigationArea::query()->where('area', $area->value)->first(); $showHeader = $row?->show_header ?? true; $showLists = $row?->show_lists ?? true; $label = \App\Modules\NavigationControl\Support\NavigationControl::areaLabel($area); @endphp
                <tr>
                    <td>
                        <form method="post" action="{{ route('navigation.area.move', $area->value) }}">@csrf
                            <x-vdbs.actions.button type="submit" name="direction" value="up" variant="tertiary" size="s" icon="chevron-up" :label="$label.' nach oben'" />
                            <x-vdbs.actions.button type="submit" name="direction" value="down" variant="tertiary" size="s" icon="chevron-down" :label="$label.' nach unten'" />
                        </form>
                    </td>
                    <td><x-vdbs.icon :name="$area->icon()" :size="18" /> {{ \App\Modules\NavigationControl\Support\NavigationControl::areaLabel($area) }}</td>
                    <td>{{ parse_url($home($area), PHP_URL_PATH) }}</td>
                    <td>
                        <form method="post" action="{{ route('navigation.area.toggle', $area->value) }}">@csrf <input type="hidden" name="field" value="show_header">
                            <x-vdbs.actions.button type="submit" variant="tertiary" size="s">{{ $showHeader ? 'Ja' : 'Nein' }}</x-vdbs.actions.button>
                        </form>
                    </td>
                    <td>
                        <form method="post" action="{{ route('navigation.area.toggle', $area->value) }}">@csrf <input type="hidden" name="field" value="show_lists">
                            <x-vdbs.actions.button type="submit" variant="tertiary" size="s">{{ $showLists ? 'Ja' : 'Nein' }}</x-vdbs.actions.button>
                        </form>
                    </td>
                    <td><x-vdbs.actions.button :href="route('navigation.area', $area->value)" variant="secondary" icon="pencil">Bearbeiten</x-vdbs.actions.button></td>
                </tr>
            @endforeach
        </x-vdbs.data.table>
    @endif
    <p class="vdbs-hint">Kopfzeile: Der Bereich steht in der ersten Zeile des Kopfmenüs. Listen: Der Bereich erscheint in Listen wie „Rollen &amp; Bereiche“ im Konto.</p>
</x-layouts.app>
