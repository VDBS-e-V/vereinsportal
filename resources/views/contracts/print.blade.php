<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Dienstleistungsvertrag {{ $seminar->number }}</title>
    <style>
        @page { size: A4; margin: 0; }
        * { box-sizing: border-box; }
        html { background: whitesmoke; }
        body { margin: 0; color: black; font: 11pt/1.45 "Segoe UI", "Aptos", Arial, sans-serif; }
        .toolbar { display: flex; gap: 12px; justify-content: center; padding: 12px; font: 14px/1.4 Arial, sans-serif; }
        .toolbar button, .toolbar a { padding: 8px 16px; border: 2px solid black; background: black; color: white; font: inherit; text-decoration: none; cursor: pointer; }
        .toolbar a { background: white; color: black; }
        .sheet { position: relative; width: 210mm; min-height: 297mm; margin: 0 auto 24px; padding: 16mm 22mm 34mm; background: white; box-shadow: 0 0 6px gray; page-break-after: always; }
        @if ($pdf) html { background: white; } .sheet { box-shadow: none; margin: 0; width: auto; min-height: 0; padding: 0 0 20mm; } @endif
        h1 { margin: 0 0 8mm; font-size: 18pt; }
        h2 { margin: 6mm 0 2mm; font-size: 11.5pt; }
        p { margin: 0 0 3mm; }
        .contract-blank { display: inline-block; min-width: 60mm; border-bottom: 1px solid black; }
        .signatures { width: 100%; margin-top: 22mm; border-collapse: separate; border-spacing: 8mm 0; }
        .signatures td { width: 50%; border-top: 1px solid black; padding-top: 2mm; font-size: 9pt; }
        .footer { position: absolute; left: 22mm; right: 22mm; bottom: 12mm; font-size: 8pt; border-top: 1px solid gray; padding-top: 2mm; }
        .number { font-size: 8pt; text-align: right; }
        @media print { html { background: white; } .toolbar { display: none; } .sheet { margin: 0; box-shadow: none; } }
    </style>
</head>
<body>
    @unless ($pdf)
    <div class="toolbar">
        <button type="button" onclick="window.print()">Drucken</button>
        @if ($pdfAvailable)
            <a href="{{ request()->fullUrlWithQuery(['format' => 'pdf']) }}">Als PDF herunterladen</a>
        @endif
        <a href="{{ route('seminars.show', $seminar) }}">Zurück zum Seminar</a>
    </div>
    @endunless

    @foreach ($pages as $page)
        <div class="sheet">
            <p class="number">{{ $page['contract']->number }}@if ($page['contract']->template_version) · Fassung {{ $page['contract']->template_version }}@endif</p>
            <h1>{{ $page['title'] }}</h1>
            {!! $page['html'] !!}
            <table class="signatures"><tr>
                <td>{{ $page['club']['club_name'] }}</td>
                <td>Auftragnehmer:in</td>
            </tr></table>
            <p class="footer">{{ $page['club']['club_name'] }} · Vorsitz: {{ $page['club']['chair'] }} · {{ $page['club']['address'] }}</p>
        </div>
    @endforeach
</body>
</html>
