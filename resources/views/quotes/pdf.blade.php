@php
    use App\Support\Money;
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Kostenvoranschlag {{ $seminar->number }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5pt; color: #0f172a; line-height: 1.45; }
        h1 { font-size: 16pt; margin: 0 0 4pt; }
        table { width: 100%; border-collapse: collapse; margin: 14pt 0; }
        th, td { border-bottom: 1px solid #cbd5e1; padding: 5pt 4pt; text-align: left; vertical-align: top; }
        th.num, td.num { text-align: right; white-space: nowrap; }
        .meta { color: #475569; font-size: 9.5pt; }
        .totals td { border: 0; padding: 2pt 4pt; }
        .totals .grand td { font-weight: bold; border-top: 1px solid #0f172a; }
    </style>
</head>
<body>
    <h1>Kostenvoranschlag</h1>
    <p class="meta">{{ $seminar->number }} · Version {{ $quote->version }} · {{ ($quote->sent_at ?? now())->format('d.m.Y') }}</p>

    <p><strong>{{ $seminar->partner->name }}</strong>@if (filled($seminar->contact_name))<br>{{ $seminar->contact_name }}@endif</p>
    <p><strong>{{ $seminar->title }}</strong>@if (filled($seminar->format)) ({{ $seminar->format }})@endif</p>

    @if (filled($quote->intro))<p>{!! nl2br(e($quote->intro)) !!}</p>@endif

    <table>
        <thead><tr><th>Position</th><th class="num">Menge</th><th class="num">Einzelpreis</th><th class="num">Summe</th></tr></thead>
        <tbody>
            @foreach ($quote->lines as $line)
                <tr>
                    <td>{{ $line->description }}</td>
                    <td class="num">{{ rtrim(rtrim(number_format((float) $line->quantity, 2, ',', ''), '0'), ',') }}@if (filled($line->unit)) {{ $line->unit }}@endif</td>
                    <td class="num">{{ Money::format($line->unit_price_cents) }}</td>
                    <td class="num">{{ Money::format($line->total()) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td></td><td class="num">Netto</td><td class="num">{{ Money::format($quote->net_cents) }}</td></tr>
        <tr><td></td><td class="num">Umsatzsteuer {{ rtrim(rtrim((string) $quote->vat_percent, '0'), '.') }} %</td><td class="num">{{ Money::format($quote->vat_cents) }}</td></tr>
        <tr class="grand"><td></td><td class="num">Gesamt</td><td class="num">{{ Money::format($quote->total_cents) }}</td></tr>
    </table>

    @if (filled($quote->outro))<p>{!! nl2br(e($quote->outro)) !!}</p>@endif

    <p class="meta">Verband für Demokratiebildung und Bibliotheken an Schulen e. V.</p>
</body>
</html>
