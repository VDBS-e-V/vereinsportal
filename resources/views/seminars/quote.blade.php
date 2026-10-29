@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Seminare', 'href' => route('seminars.index')], ['label' => $seminar->title, 'href' => route('seminars.show', $seminar)], ['label' => 'Kostenvoranschlag Fassung '.$quote->version]];
    $inputs = $quote->inputs;
    $dayLabel = $seminar->type === \App\Modules\Seminars\Enums\SeminarType::SvDays ? 'Tage' : 'Treffen';
@endphp

<x-layouts.app :title="'Kostenvoranschlag Fassung '.$quote->version" width="wide">
    <x-vdbs.layout.page-header :title="'Kostenvoranschlag Fassung '.$quote->version" :lead="$seminar->title" :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button type="button" variant="secondary" icon="printer" onclick="window.print()">Drucken</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="tertiary" :href="route('seminars.show', $seminar)">Zum Seminar</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    <x-vdbs.layout.grid :columns="2" align="start">
        <section aria-labelledby="quote-data-heading">
            <h2 id="quote-data-heading" style="font-size:var(--font-size-h3)">Angaben</h2>
            <x-vdbs.data.detail-list layout="inline">
                <x-vdbs.data.detail-item label="Schule">{{ $seminar->partner->name }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Leistung">{{ $seminar->type->label() }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item :label="$dayLabel">{{ $inputs['days'] }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Teamende">{{ $inputs['team'] }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Stunden pro Tag">{{ rtrim(rtrim(number_format((float) $inputs['hours'], 1, ',', ''), '0'), ',') }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Gruppengröße">{{ $inputs['group'] }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Anfahrt">{{ $inputs['inside_abc'] ? 'Innerhalb des Tarifbereichs ABC' : 'Außerhalb des Tarifbereichs ABC' }}</x-vdbs.data.detail-item>
            </x-vdbs.data.detail-list>
        </section>

        <section aria-labelledby="quote-meta-heading">
            <h2 id="quote-meta-heading" style="font-size:var(--font-size-h3)">Stand</h2>
            <x-vdbs.data.detail-list layout="inline">
                <x-vdbs.data.detail-item label="Fassung">{{ $quote->version }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Erstellt am">{{ $quote->created_at?->format('d.m.Y') }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Erstellt von">{{ $quote->createdBy ? ($quote->createdBy->person?->fullName() ?: $quote->createdBy->email) : null }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Preisstand">{{ $quote->priceList->label }}</x-vdbs.data.detail-item>
            </x-vdbs.data.detail-list>
        </section>
    </x-vdbs.layout.grid>

    <section aria-labelledby="quote-lines-heading" style="margin-top:var(--space-8)">
        <h2 id="quote-lines-heading" style="font-size:var(--font-size-h3)">Posten</h2>
        @include('seminars._lines', ['lines' => $quote->lines, 'total' => $quote->total_cents, 'days' => $inputs['days']])
        <p style="color:var(--text-muted);font-size:var(--font-size-small)">Unverbindlicher Kostenvoranschlag. Alle Beträge in Euro, die Preise stammen aus der Preisliste „{{ $quote->priceList->label }}“.</p>
    </section>
</x-layouts.app>
