@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Admins'], ['label' => 'Navigation']];
    $tab = 'headers';
@endphp

<x-layouts.app title="Navigation" width="wide">
    <x-vdbs.layout.page-header title="Übersicht der Header" lead="Die Seiten im Kopfmenü je Bereich. Titel, Reihenfolge und Sichtbarkeit ändern Sie je Bereich, dazu eigene Links mit Unterseiten." :breadcrumb="$crumbs" />

    <nav class="vdbs-task-filter" aria-label="Web-Control">
        <x-vdbs.actions.button :href="route('navigation.edit')" variant="{{ $tab === 'areas' ? 'primary' : 'secondary' }}" icon="layout-dashboard">Bereiche</x-vdbs.actions.button>
        <x-vdbs.actions.button :href="route('navigation.headers')" variant="{{ $tab === 'headers' ? 'primary' : 'secondary' }}" icon="menu">Header</x-vdbs.actions.button>
    </nav>

    <x-vdbs.data.table caption="Header der Bereiche" :columns="[['key' => 'area', 'label' => 'Bereich'], ['key' => 'pages', 'label' => 'Seiten'], ['key' => 'link', 'label' => 'Link'], ['key' => 'act', 'label' => 'Bearbeiten']]">
        @foreach ($rows as $row)
            <tr>
                <td>{{ \App\Modules\NavigationControl\Support\NavigationControl::areaLabel($row['area']) }}</td>
                <td>{{ $row['pages'] }}</td>
                <td>{{ parse_url(route(\App\Support\PortalNavigation::catalog()[$row['area']->value]['home']), PHP_URL_PATH) }}</td>
                <td><x-vdbs.actions.button :href="route('navigation.header', $row['area']->value)" variant="secondary" icon="pencil">Bearbeiten</x-vdbs.actions.button></td>
            </tr>
        @endforeach
    </x-vdbs.data.table>
</x-layouts.app>
