@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Seiten']];
@endphp

<x-layouts.app title="Seiten" width="wide">
    <x-vdbs.layout.page-header title="Seiten" lead="Impressum, Datenschutzerklärung, Barrierefreiheit und Nutzungsbedingungen. Die Texte sind öffentlich sichtbar." :breadcrumb="$crumbs" />

    <x-vdbs.data.table caption="Rechtstext-Seiten" :columns="[['key' => 'title', 'label' => 'Seite'], ['key' => 'status', 'label' => 'Stand'], ['key' => 'by', 'label' => 'Zuletzt geändert von'], ['key' => 'view', 'label' => 'Öffentlich']]">
        @foreach ($pages as $row)
            <tr class="vdbs-table__row--link">
                <td><a class="vdbs-table__row-link" href="{{ route('pages.admin.edit', $row->key) }}">{{ $row->pageKey()->title() }}</a></td>
                <td>
                    @if ($row->isDefault())
                        <x-vdbs.data.badge tone="warning">Standardtext</x-vdbs.data.badge>
                    @else
                        {{ $row->updated_at->format('d.m.Y, H:i') }}
                    @endif
                </td>
                <td>{{ $row->updatedBy?->name ?? '–' }}</td>
                <td><a href="{{ route('pages.show', $row->key) }}">/{{ $row->key }}</a></td>
            </tr>
        @endforeach
    </x-vdbs.data.table>
</x-layouts.app>
