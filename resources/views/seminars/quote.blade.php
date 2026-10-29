@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Kostenvoranschläge', 'href' => route('seminar-quotes.index')], ['label' => $quote->partner->name.', '.$quote->created_at?->format('d.m.Y')]];
    $money = fn ($cents) => \App\Modules\Seminars\Support\Money::format($cents);
    $author = fn ($user) => $user ? ($user->person?->fullName() ?: $user->email) : null;
    $fassung = $data['current'] ? null : $data['version'];
    $lines = fn (array $position): array => array_merge(\App\Modules\Seminars\Support\QuoteTexts::lines($position['content']), \App\Modules\Seminars\Support\QuoteTexts::lines($position['extra'] ?? ''));
@endphp

<x-layouts.app title="Kostenvoranschlag" width="wide">
    <x-vdbs.layout.page-header title="Kostenvoranschlag" :lead="$quote->partner->name" :breadcrumb="$crumbs">
        <x-slot:meta><x-vdbs.data.badge :tone="$data['current'] ? 'success' : 'warning'">Fassung {{ $data['version'] }}{{ $data['current'] ? ' (aktuell)' : '' }}</x-vdbs.data.badge></x-slot:meta>

        <x-slot:actions>
            <x-vdbs.actions.button variant="secondary" :href="route('seminar-quotes.print', ['quote' => $quote, 'fassung' => $fassung])" icon="printer">Schreiben drucken</x-vdbs.actions.button>
            @if ($canManage)
                <x-vdbs.actions.button :href="route('seminar-quotes.edit', ['quote' => $quote, 'fassung' => $fassung])" icon="pencil">{{ $data['current'] ? 'Ändern' : 'Daraus neue Fassung machen' }}</x-vdbs.actions.button>
                <x-vdbs.actions.button variant="secondary" :href="route('seminar-quotes.create', ['vorlage' => $quote->id])" icon="copy">Als Vorlage für einen neuen verwenden</x-vdbs.actions.button>
                <x-vdbs.actions.button variant="danger" shape="outline" icon="trash-2" data-vdbs-dialog-open="delete-quote">Löschen</x-vdbs.actions.button>
            @endif
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    <x-vdbs.forms.error-summary />

    @unless ($data['current'])
        <x-vdbs.feedback.alert variant="warning">Das ist die frühere Fassung {{ $data['version'] }} vom {{ $data['at']?->format('d.m.Y, H:i') }}. <a href="{{ route('seminar-quotes.show', $quote) }}">Zur aktuellen Fassung {{ $quote->version }}</a></x-vdbs.feedback.alert>
    @endunless

    <x-vdbs.layout.grid :columns="2" align="start">
        <section aria-labelledby="quote-data-heading">
            <h2 id="quote-data-heading" style="font-size:var(--font-size-h3)">Angaben</h2>
            <x-vdbs.data.detail-list layout="inline">
                <x-vdbs.data.detail-item label="Schule"><a href="{{ route('partners.show', $quote->partner) }}">{{ $quote->partner->name }}</a></x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Seminar">@if ($quote->seminar)<a href="{{ route('seminars.show', $quote->seminar) }}">{{ $quote->seminar->title }}</a>@endif</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Endsumme">{{ $money($data['total_cents']) }}</x-vdbs.data.detail-item>
            </x-vdbs.data.detail-list>
        </section>

        <section aria-labelledby="quote-meta-heading">
            <h2 id="quote-meta-heading" style="font-size:var(--font-size-h3)">Stand dieser Fassung</h2>
            <x-vdbs.data.detail-list layout="inline">
                <x-vdbs.data.detail-item label="Fassung">{{ $data['version'] }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Erstellt am">{{ $data['at']?->format('d.m.Y, H:i') }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Erstellt von">{{ $data['author'] }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Änderung">{{ $data['comment'] }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Preisstand">{{ $data['price_list']->label }}</x-vdbs.data.detail-item>
            </x-vdbs.data.detail-list>
        </section>
    </x-vdbs.layout.grid>

    @foreach ($data['positions'] as $n => $position)
        @php $unit = \App\Modules\Seminars\Enums\SeminarType::tryFrom($position['product'])?->unit() ?? 'Stück'; @endphp
        <section aria-labelledby="position-{{ $n }}-heading" style="margin-top:var(--space-8)">
            <h2 id="position-{{ $n }}-heading" style="font-size:var(--font-size-h3)">Position {{ $n + 1 }}: {{ $position['title'] }}</h2>
            <p>{{ $position['inputs']['days'] }} {{ $unit }}, {{ $position['inputs']['team'] }} Teamende, {{ rtrim(rtrim(number_format((float) $position['inputs']['hours'], 1, ',', ''), '0'), ',') }} Stunden pro Tag, {{ $position['inputs']['group'] }} Teilnehmende, {{ $position['inputs']['inside_abc'] ? 'innerhalb' : 'außerhalb' }} des Tarifbereichs ABC.</p>
            <ul>
                @foreach ($lines($position) as $line)
                    <li>{{ $line }}</li>
                @endforeach
            </ul>
            @include('seminars._lines', ['lines' => $position['lines'], 'total' => $position['total_cents'], 'days' => $position['inputs']['days'], 'totalLabel' => 'Summe der Position'])
        </section>
    @endforeach

    <p style="margin-top:var(--space-6)"><strong>Endsumme: {{ $money($data['total_cents']) }}</strong></p>
    <p style="color:var(--text-muted);font-size:var(--font-size-small)">Unverbindlicher Kostenvoranschlag. Alle Beträge in Euro, die Preise stammen aus der Preisliste „{{ $data['price_list']->label }}“.</p>

    <section aria-labelledby="versions-heading" style="margin-top:var(--space-8)">
        <h2 id="versions-heading" style="font-size:var(--font-size-h3)">Versionsverlauf</h2>

        <x-vdbs.data.table caption="Fassungen des Kostenvoranschlags" :columns="[['key' => 'version', 'label' => 'Fassung'], ['key' => 'date', 'label' => 'Datum'], ['key' => 'by', 'label' => 'Von'], ['key' => 'total', 'label' => 'Endsumme'], ['key' => 'comment', 'label' => 'Änderung'], ['key' => 'action', 'label' => 'Ansicht']]">
            @foreach ($quote->versions as $version)
                <tr>
                    <td>Fassung {{ $version->version }} @if ($version->version === $quote->version)<x-vdbs.data.badge tone="success">Aktuell</x-vdbs.data.badge>@endif</td>
                    <td>{{ $version->created_at?->format('d.m.Y, H:i') }}</td>
                    <td>{{ $author($version->createdBy) ?? '–' }}</td>
                    <td>{{ $money($version->total_cents) }}</td>
                    <td>{{ $version->comment ?? ($version->version === 1 ? 'Erstellt' : '–') }}</td>
                    <td>
                        @if ($version->version !== $data['version'])
                            <a href="{{ route('seminar-quotes.show', ['quote' => $quote, 'fassung' => $version->version === $quote->version ? null : $version->version]) }}">Ansehen</a>
                        @else
                            Angezeigt
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-vdbs.data.table>
    </section>

    @if ($canManage)
        <x-vdbs.overlays.confirm-dialog id="delete-quote" title="Kostenvoranschlag löschen?" variant="danger" :action="route('seminar-quotes.destroy', $quote)" method="delete" confirm-label="Kostenvoranschlag löschen">
            <p>Der Kostenvoranschlag für <strong>{{ $quote->partner->name }}</strong> mit einer Endsumme von {{ $money($quote->total_cents) }} wird mit allen {{ $quote->versions->count() }} Fassungen dauerhaft gelöscht.</p>
        </x-vdbs.overlays.confirm-dialog>
    @endif
</x-layouts.app>
