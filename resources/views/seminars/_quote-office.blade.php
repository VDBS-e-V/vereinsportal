@php
    use App\Modules\Quotes\Enums\QuoteStatus;
    use App\Modules\Seminars\Enums\SeminarStatus;
    use App\Support\Money;

    $quotes = $seminar->quotes()->get();
    $open = $quotes->first(fn ($quote): bool => $quote->status->isEditable());
    $canCreate = in_array($seminar->status, [SeminarStatus::InReview, SeminarStatus::QuoteSent], true) && $open === null;
@endphp

<h2 style="font-size:var(--font-size-h3);margin-top:var(--space-8)">Kostenvoranschlag</h2>

@error('quote')
    <x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert>
@enderror

@if ($quotes->isEmpty())
    <p>Es gibt noch keinen Kostenvoranschlag.@if (! $canCreate) Er lässt sich anlegen, sobald das Seminar in Prüfung ist.@endif</p>
@else
    <x-vdbs.data.table caption="Versionen des Kostenvoranschlags" :columns="[['key' => 'version', 'label' => 'Version'], ['key' => 'status', 'label' => 'Stand'], ['key' => 'total', 'label' => 'Gesamt'], ['key' => 'links', 'label' => 'Ansehen']]">
        @foreach ($quotes as $quote)
            <tr>
                <td>{{ $quote->version }}</td>
                <td><x-vdbs.data.badge :tone="$quote->status->tone()">{{ $quote->status->label() }}</x-vdbs.data.badge></td>
                <td>{{ Money::format($quote->total_cents) }}</td>
                <td><a href="{{ route('quotes.office.edit', [$seminar, $quote]) }}">{{ $quote->status->isEditable() ? 'Bearbeiten' : 'Öffnen' }}</a> · <a href="{{ route('quotes.pdf', $quote) }}">PDF</a></td>
            </tr>
        @endforeach
    </x-vdbs.data.table>
@endif

@if ($canCreate)
    <x-vdbs.forms.form :action="route('quotes.office.store', $seminar)" :summary="false" style="margin-top:var(--space-4)">
        <x-slot:actions><x-vdbs.actions.button type="submit" variant="secondary" icon="plus">{{ $quotes->isEmpty() ? 'Kostenvoranschlag anlegen' : 'Neue Version anlegen' }}</x-vdbs.actions.button></x-slot:actions>
    </x-vdbs.forms.form>
@endif
