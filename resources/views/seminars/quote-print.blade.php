@php
    $money = fn ($cents) => \App\Modules\Seminars\Support\Money::format($cents);
    $logo = is_file(public_path($letter['logo'])) ? asset($letter['logo']) : asset('images/brand/vdbs-bildmarke-breit.png');
    $board = $letter['board'];
    $footer = $letter['footer'];
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Kostenvoranschlag {{ $quote->partner->name }} – {{ $data['at']?->format('d.m.Y') }}</title>
    <style>
        @page { size: A4; margin: 0; }
        * { box-sizing: border-box; }
        html { background: whitesmoke; }
        body { margin: 0; color: black; font: 11pt/1.4 "Segoe UI", "Aptos", Arial, sans-serif; }
        .toolbar { display: flex; gap: 12px; align-items: center; justify-content: center; padding: 12px; font: 14px/1.4 Arial, sans-serif; }
        .toolbar button, .toolbar a { padding: 8px 16px; border: 2px solid black; background: black; color: white; font: inherit; text-decoration: none; cursor: pointer; }
        .toolbar a { background: white; color: black; }
        .sheet { position: relative; width: 210mm; min-height: 297mm; margin: 0 auto 24px; padding: 14mm 22mm 36mm; background: white; box-shadow: 0 0 6px gray; }
        .logo { display: block; width: 98mm; height: auto; }
        .head { display: grid; grid-template-columns: 1fr 66mm; gap: 8mm; margin-top: 8mm; }
        .sender { font-size: 7pt; margin: 0 0 8mm; }
        .recipient p, .info p { margin: 0 0 1mm; }
        .info { font-size: 8.5pt; }
        .info p { margin-bottom: 2mm; }
        h1 { margin: 22mm 0 6mm; font-size: 15pt; }
        .date { display: grid; grid-template-columns: 38mm 1fr; margin-bottom: 4mm; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 2mm 2mm; border-top: 1px solid black; border-bottom: 1px solid black; text-align: left; }
        td { padding: 2mm 2mm; vertical-align: top; }
        .pos { width: 13mm; text-align: center; }
        .item { width: 50mm; }
        .price { width: 30mm; text-align: right; white-space: nowrap; }
        th.pos { text-align: center; }
        th.price { text-align: right; }
        ul { margin: 0; padding: 0; list-style: none; }
        li { position: relative; padding-left: 5mm; }
        li::before { content: "-"; position: absolute; left: 0; }
        tbody tr:last-child td { border-bottom: 1px solid black; padding-bottom: 3mm; }
        tbody tr { break-inside: avoid; }
        .total td { padding-top: 2mm; font-weight: bold; }
        .total .label { text-align: right; }
        .total .price { border-bottom: 2px solid black; }
        .total .price span { display: inline-block; padding-bottom: 1mm; }
        .total td { border-bottom: 0; }
        .foot { position: absolute; left: 22mm; right: 22mm; bottom: 14mm; display: grid; grid-template-columns: 1.2fr 1.05fr 0.95fr 1.7fr 0.4fr; gap: 4mm; padding-top: 2mm; border-top: 1px solid black; font-size: 6.5pt; line-height: 1.35; }
        .foot p { margin: 0; }
        .foot .page { text-align: right; }
        @media print {
            html { background: white; }
            .toolbar { display: none; }
            .sheet { margin: 0; box-shadow: none; page-break-after: avoid; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Drucken oder als PDF speichern</button>
        <a href="{{ route('seminar-quotes.show', ['quote' => $quote, 'fassung' => $data['current'] ? null : $data['version']]) }}">Zurück</a>
    </div>

    <main class="sheet">
        <img class="logo" src="{{ $logo }}" alt="Verband für Demokratiebildung und Bibliotheken an Schule e.V.">

        <div class="head">
            <div>
                <p class="sender">{{ $letter['sender_line'] }}</p>
                <div class="recipient">
                    @foreach ($recipientLines as $line)
                        <p>{{ $line }}</p>
                    @endforeach
                </div>
            </div>
            <div class="info">
                <p><strong>{{ $board['title'] }}</strong></p>
                <p>@foreach ($board['lines'] as $line){{ $line }}@if (! $loop->last)<br>@endif @endforeach</p>
                <p>{{ $board['phone'] }}</p>
                @if ($author)
                    <p>bearbeitet von:<br><strong>{{ $author }}</strong></p>
                @endif
                <p>{{ $board['office_email'] }}</p>
                <p>{{ $board['website'] }}<br>{{ $board['contact_email'] }}</p>
            </div>
        </div>

        <h1>Kostenvoranschlag</h1>

        <div class="date"><span>Datum:</span><span>{{ $data['at']?->format('d.m.Y') }}</span></div>

        <table>
            <thead>
                <tr><th class="pos">Pos.</th><th class="item">Posten</th><th>Inhalt</th><th class="price">Preis</th></tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    <tr>
                        <td class="pos">{{ $loop->iteration }}</td>
                        <td>{{ $item['title'] }}</td>
                        <td><ul>@foreach ($item['lines'] as $line)<li>{{ $line }}</li>@endforeach</ul></td>
                        <td class="price">{{ $money($item['total_cents']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total"><td colspan="3" class="label">Endsumme</td><td class="price"><span>{{ $money($data['total_cents']) }}</span></td></tr>
            </tfoot>
        </table>

        <footer class="foot">
            <div><p>{{ $footer['organization'] }}</p><p>{{ $footer['chair_label'] }}<br>{{ $footer['chair'] }}</p></div>
            <div><p><strong>{{ $footer['address_title'] }}</strong></p>@foreach ($footer['address_lines'] as $line)<p>{{ $line }}</p>@endforeach</div>
            <div><p><strong>{{ $footer['contact_title'] }}</strong></p>@foreach ($footer['contact_lines'] as $line)<p>{{ $line }}</p>@endforeach</div>
            <div><p>{{ $footer['bank_title'] }}</p>@foreach ($footer['bank_lines'] as $line)<p>{{ $line }}</p>@endforeach</div>
            <div class="page"><p>Seite<br>1 von 1</p></div>
        </footer>
    </main>
</body>
</html>
