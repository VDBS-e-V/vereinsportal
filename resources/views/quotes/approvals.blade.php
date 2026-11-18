@php
    use App\Support\Money;

    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'KVA-Freigabe']];
@endphp

<x-layouts.app title="KVA-Freigabe" width="wide">
    <x-vdbs.layout.page-header title="Kostenvoranschläge zur Freigabe" lead="Prüfen Sie das PDF und geben Sie den Kostenvoranschlag intern frei. Danach kann die Geschäftsstelle ihn an die Schule senden." :breadcrumb="$crumbs" />

    @error('quote')
        <x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert>
    @enderror

    @if ($quotes->isEmpty())
        <x-vdbs.feedback.empty-state title="Nichts zur Freigabe" icon="check"><p>Zurzeit wartet kein Kostenvoranschlag auf Ihre Freigabe.</p></x-vdbs.feedback.empty-state>
    @else
        <x-vdbs.data.table caption="Kostenvoranschläge zur Freigabe" :columns="[['key' => 'seminar', 'label' => 'Seminar'], ['key' => 'school', 'label' => 'Schule'], ['key' => 'version', 'label' => 'Version'], ['key' => 'total', 'label' => 'Gesamt'], ['key' => 'actions', 'label' => 'Aktion']]">
            @foreach ($quotes as $quote)
                <tr>
                    <td>{{ $quote->seminar->number }} · {{ $quote->seminar->title }}</td>
                    <td>{{ $quote->seminar->partner->name }}</td>
                    <td>{{ $quote->version }}</td>
                    <td>{{ Money::format($quote->total_cents) }}</td>
                    <td>
                        <a href="{{ route('quotes.pdf', $quote) }}">PDF</a>
                        <form method="post" action="{{ route('quotes.approval.approve', $quote) }}" style="display:inline">@csrf
                            <x-vdbs.actions.button type="submit" size="s">Freigeben</x-vdbs.actions.button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </x-vdbs.data.table>
    @endif
</x-layouts.app>
