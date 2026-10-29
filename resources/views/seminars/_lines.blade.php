@php $money = fn ($cents) => \App\Modules\Seminars\Support\Money::format($cents); @endphp

<x-vdbs.data.table caption="Posten des Kostenvoranschlags" :columns="[['key' => 'item', 'label' => 'Posten'], ['key' => 'qty', 'label' => 'Menge'], ['key' => 'unit', 'label' => 'Einzelpreis'], ['key' => 'days', 'label' => 'Tage'], ['key' => 'total', 'label' => 'Betrag']]">
    @foreach ($lines as $line)
        <tr>
            <td>{{ $line['label'] }}</td>
            <td>{{ rtrim(rtrim(number_format($line['quantity'], 2, ',', ''), '0'), ',') }} {{ $line['unit'] }}</td>
            <td>{{ $money($line['unit_cents']) }}</td>
            <td>{{ $line['per_day'] ? $days : 1 }}</td>
            <td>{{ $money($line['total_cents']) }}</td>
        </tr>
    @endforeach
    <tr>
        <td colspan="4"><strong>{{ $totalLabel ?? 'Gesamtbetrag' }}</strong></td>
        <td><strong>{{ $money($total) }}</strong></td>
    </tr>
</x-vdbs.data.table>
