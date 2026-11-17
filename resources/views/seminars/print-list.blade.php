<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Druckliste {{ $seminar->number }}</title>
    <style>
        @page { size: A4; margin: 15mm; }
        body { margin: 0; color: black; font: 10.5pt/1.4 "Segoe UI", Arial, sans-serif; }
        h1 { font-size: 16pt; margin: 0 0 4mm; }
        table { width: 100%; border-collapse: collapse; margin: 4mm 0; }
        th, td { border: 1px solid gray; padding: 2mm 3mm; text-align: left; vertical-align: top; }
        th { background: #eee; }
        .toolbar { padding: 3mm 0; }
        @media print { .toolbar { display: none; } }
    </style>
</head>
<body>
    @unless ($pdf)<div class="toolbar"><button type="button" onclick="window.print()">Drucken</button> <a href="{{ route('seminar-print.show', $seminar) }}">Zurück zum Druckplaner</a></div>@endunless
    <h1>Druckliste {{ $seminar->number }}: {{ $seminar->title }}</h1>
    <p>Schule {{ $seminar->partner?->name }}. Erstellt am {{ now()->format('d.m.Y') }}.</p>
    <table>
        <thead><tr><th>Dokument</th><th>Seiten</th><th>Exemplare</th><th>Farbe</th><th>Seiten pro Blatt</th><th>Beidseitig</th><th>Blätter</th></tr></thead>
        <tbody>
        @foreach ($summary['rows'] as $row)
            <tr>
                <td>{{ $row['document']->title ?: $row['document']->original_name }}</td>
                <td>{{ $row['pages'] !== '' ? $row['pages'] : 'alle' }} ({{ $row['selected'] }})</td>
                <td>{{ $row['copies'] }}</td>
                <td>{{ $row['color'] === 'color' ? 'Farbe' : 'Schwarzweiß' }}</td>
                <td>{{ $row['per_sheet'] }}</td>
                <td>{{ $row['duplex'] ? 'ja' : 'nein' }}</td>
                <td>{{ $row['sheets'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @php $t = $summary['totals']; @endphp
    <p><strong>Zusammen:</strong> {{ $t['sheets'] }} Blätter ({{ $t['sheets_bw'] }} schwarzweiß, {{ $t['sheets_color'] }} in Farbe), {{ $t['pages_bw'] + $t['pages_color'] }} Seiten.</p>
</body>
</html>
