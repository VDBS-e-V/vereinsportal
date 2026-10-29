@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Preise']];
    $money = fn ($cents) => \App\Modules\Seminars\Support\Money::format($cents);
@endphp

<x-layouts.app title="Preise" width="wide">
    <x-vdbs.layout.page-header title="Preise" lead="Preisliste für Kostenvoranschläge. Eine Änderung legt eine neue Fassung an; bestehende Kostenvoranschläge behalten ihren Preisstand." :breadcrumb="$crumbs">
        @if ($canManage)
            <x-slot:actions><x-vdbs.actions.button :href="route('seminar-prices.create')" icon="pencil">Neue Fassung anlegen</x-vdbs.actions.button></x-slot:actions>
        @endif
    </x-vdbs.layout.page-header>

    <section aria-labelledby="current-heading">
        <h2 id="current-heading" style="font-size:var(--font-size-h3)">Aktuell: {{ $current->label }}</h2>
        <p>Gültig ab {{ $current->valid_from->format('d.m.Y') }}.</p>

        <x-vdbs.data.table caption="Aktuelle Preise" :columns="[['key' => 'item', 'label' => 'Posten'], ['key' => 'price', 'label' => 'Preis'], ['key' => 'note', 'label' => 'Hinweis']]">
            @foreach ($items as $key => [$label, $note])
                <tr>
                    <td>{{ $label }}</td>
                    <td>{{ $money($current->prices[$key] ?? 0) }}</td>
                    <td>{{ $note }}</td>
                </tr>
            @endforeach
        </x-vdbs.data.table>
    </section>

    <section aria-labelledby="versions-heading" style="margin-top:var(--space-8)">
        <h2 id="versions-heading" style="font-size:var(--font-size-h3)">Fassungen</h2>
        <x-vdbs.data.table caption="Fassungen der Preisliste" :columns="[['key' => 'label', 'label' => 'Bezeichnung'], ['key' => 'from', 'label' => 'Gültig ab'], ['key' => 'created', 'label' => 'Angelegt']]">
            @foreach ($lists as $list)
                <tr>
                    <td>{{ $list->label }} @if ($list->id === $current->id)<x-vdbs.data.badge tone="success">Aktuell</x-vdbs.data.badge>@endif</td>
                    <td>{{ $list->valid_from->format('d.m.Y') }}</td>
                    <td>{{ $list->created_at?->format('d.m.Y') }}</td>
                </tr>
            @endforeach
        </x-vdbs.data.table>
    </section>
</x-layouts.app>
