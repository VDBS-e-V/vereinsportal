@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Kostenvoranschläge', 'href' => route('seminar-quotes.index')], ['label' => $quote->partner->name.', '.$quote->created_at?->format('d.m.Y')]];
    $money = fn ($cents) => \App\Modules\Seminars\Support\Money::format($cents);
@endphp

<x-layouts.app title="Kostenvoranschlag" width="wide">
    <x-vdbs.layout.page-header title="Kostenvoranschlag" :lead="$quote->partner->name" :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button variant="secondary" :href="route('seminar-quotes.print', $quote)" icon="printer">Schreiben drucken</x-vdbs.actions.button>
            @if ($canManage)
                <x-vdbs.actions.button variant="secondary" :href="route('seminar-quotes.create', ['vorlage' => $quote->id])" icon="copy">Als Vorlage verwenden</x-vdbs.actions.button>
                <x-vdbs.actions.button variant="danger" shape="outline" icon="trash-2" data-vdbs-dialog-open="delete-quote">Löschen</x-vdbs.actions.button>
            @endif
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    <x-vdbs.forms.error-summary />

    <x-vdbs.layout.grid :columns="2" align="start">
        <section aria-labelledby="quote-data-heading">
            <h2 id="quote-data-heading" style="font-size:var(--font-size-h3)">Angaben</h2>
            <x-vdbs.data.detail-list layout="inline">
                <x-vdbs.data.detail-item label="Schule"><a href="{{ route('partners.show', $quote->partner) }}">{{ $quote->partner->name }}</a></x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Seminar">@if ($quote->seminar)<a href="{{ route('seminars.show', $quote->seminar) }}">{{ $quote->seminar->title }}</a>@endif</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Endsumme">{{ $money($quote->total_cents) }}</x-vdbs.data.detail-item>
            </x-vdbs.data.detail-list>
        </section>

        <section aria-labelledby="quote-meta-heading">
            <h2 id="quote-meta-heading" style="font-size:var(--font-size-h3)">Stand</h2>
            <x-vdbs.data.detail-list layout="inline">
                <x-vdbs.data.detail-item label="Erstellt am">{{ $quote->created_at?->format('d.m.Y') }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Erstellt von">{{ $quote->createdBy ? ($quote->createdBy->person?->fullName() ?: $quote->createdBy->email) : null }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Preisstand">{{ $quote->priceList->label }}</x-vdbs.data.detail-item>
            </x-vdbs.data.detail-list>
        </section>
    </x-vdbs.layout.grid>

    @foreach ($quote->positions as $n => $position)
        @php $unit = \App\Modules\Seminars\Enums\SeminarType::tryFrom($position['product'])?->unit() ?? 'Stück'; @endphp
        <section aria-labelledby="position-{{ $n }}-heading" style="margin-top:var(--space-8)">
            <h2 id="position-{{ $n }}-heading" style="font-size:var(--font-size-h3)">Position {{ $n + 1 }}: {{ $position['title'] }}</h2>
            <p>{{ $position['inputs']['days'] }} {{ $unit }}, {{ $position['inputs']['team'] }} Teamende, {{ rtrim(rtrim(number_format((float) $position['inputs']['hours'], 1, ',', ''), '0'), ',') }} Stunden pro Tag, {{ $position['inputs']['group'] }} Teilnehmende, {{ $position['inputs']['inside_abc'] ? 'innerhalb' : 'außerhalb' }} des Tarifbereichs ABC.</p>
            @include('seminars._lines', ['lines' => $position['lines'], 'total' => $position['total_cents'], 'days' => $position['inputs']['days'], 'totalLabel' => 'Summe der Position'])
        </section>
    @endforeach

    <p style="margin-top:var(--space-6)"><strong>Endsumme: {{ $money($quote->total_cents) }}</strong></p>
    <p style="color:var(--text-muted);font-size:var(--font-size-small)">Unverbindlicher Kostenvoranschlag. Alle Beträge in Euro, die Preise stammen aus der Preisliste „{{ $quote->priceList->label }}“.</p>

    @if ($canManage)
        <x-vdbs.overlays.confirm-dialog id="delete-quote" title="Kostenvoranschlag löschen?" variant="danger" :action="route('seminar-quotes.destroy', $quote)" method="delete" confirm-label="Kostenvoranschlag löschen">
            <p>Der Kostenvoranschlag für <strong>{{ $quote->partner->name }}</strong> mit einer Endsumme von {{ $money($quote->total_cents) }} wird dauerhaft gelöscht.</p>
        </x-vdbs.overlays.confirm-dialog>
    @endif
</x-layouts.app>
