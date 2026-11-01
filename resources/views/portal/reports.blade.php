@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Vorstand'], ['label' => 'Berichte']];
@endphp

<x-layouts.app title="Jahresbericht" width="medium">
    <x-vdbs.layout.page-header title="Jahresbericht {{ $year }}" lead="Kennzahlen des Vereins für ein Kalenderjahr. Nur Zahlen, keine Personendaten." :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button variant="secondary" :href="route('board.reports.export', ['jahr' => $year])" icon="download">Als CSV herunterladen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    <nav aria-label="Jahr wählen" style="margin-bottom:var(--space-5)">
        <ul style="display:flex;flex-wrap:wrap;gap:var(--space-2);margin:0;padding:0;list-style:none">
            @foreach ($years as $option)
                <li><x-vdbs.actions.button size="s" :variant="$option === $year ? 'primary' : 'secondary'" :shape="$option === $year ? 'solid' : 'outline'" :href="route('board.reports', ['jahr' => $option])">{{ $option }}</x-vdbs.actions.button></li>
            @endforeach
        </ul>
    </nav>

    @foreach ($rows as $group => $items)
        <section aria-labelledby="group-{{ \Illuminate\Support\Str::slug($group) }}" style="margin-bottom:var(--space-6)">
            <h2 id="group-{{ \Illuminate\Support\Str::slug($group) }}" style="font-size:var(--font-size-h3)">{{ $group }}</h2>
            <x-vdbs.data.detail-list layout="inline">
                @foreach ($items as $item)
                    <x-vdbs.data.detail-item :label="$item['label']">{{ $item['value'] }}</x-vdbs.data.detail-item>
                @endforeach
            </x-vdbs.data.detail-list>
        </section>
    @endforeach

    <p style="color:var(--text-muted);font-size:var(--font-size-small)">Seminare und Veranstaltungen zählen in dem Jahr, in dem ihre Termine liegen. Als durchgeführt gelten Seminare im Status „Durchgeführt“ oder „Abgeschlossen“. Mitglieder am 31.12. sind alle mit laufender Mitgliedschaft an diesem Tag.</p>
</x-layouts.app>
