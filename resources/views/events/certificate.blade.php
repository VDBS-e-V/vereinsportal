@php
    $event = $registration->event;
    $sessions = $registration->attendances->map->session->filter()->sortBy('starts_at');
    $footer = (array) config('portal.letter.footer', []);
    $logo = asset('images/brand/vdbs-bildmarke-breit.png');
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Teilnahmebestätigung {{ $registration->name }}</title>
    <style>
        @page { size: A4; margin: 0; }
        * { box-sizing: border-box; }
        html { background: whitesmoke; }
        body { margin: 0; color: black; font: 12pt/1.5 "Segoe UI", Arial, sans-serif; }
        .toolbar { display: flex; gap: 12px; align-items: center; justify-content: center; padding: 12px; font: 14px/1.4 Arial, sans-serif; }
        .toolbar button, .toolbar a { padding: 8px 16px; border: 2px solid black; background: black; color: white; font: inherit; text-decoration: none; cursor: pointer; }
        .toolbar a { background: white; color: black; }
        .sheet { width: 210mm; min-height: 297mm; margin: 0 auto 24px; padding: 20mm 24mm; background: white; box-shadow: 0 0 6px gray; }
        .logo { display: block; width: 90mm; height: auto; margin-bottom: 24mm; }
        h1 { margin: 0 0 12mm; font-size: 22pt; }
        .name { margin: 8mm 0 2mm; font-size: 18pt; font-weight: bold; }
        ul { margin: 4mm 0 10mm; padding-left: 6mm; }
        .footer { margin-top: 30mm; font-size: 9pt; color: #333; border-top: 1px solid black; padding-top: 4mm; }
        @media print { html { background: white; } .toolbar { display: none; } .sheet { margin: 0; box-shadow: none; } }
    </style>
</head>
<body>
    <div class="toolbar"><button type="button" onclick="window.print()">Drucken oder als PDF speichern</button><a href="{{ $back }}">Zurück</a></div>
    <main class="sheet">
        <img class="logo" src="{{ $logo }}" alt="VDBS e. V.">
        <h1>Teilnahmebestätigung</h1>
        <p>Hiermit wird bestätigt, dass</p>
        <p class="name">{{ $registration->name }}</p>
        <p>({{ $registration->class_name }}, {{ $registration->school }})</p>
        <p>an der Veranstaltung <strong>{{ $event->title }}</strong>@if ($event->location) ({{ $event->location }})@endif teilgenommen hat, und zwar an folgenden Terminen:</p>
        <ul>@foreach ($sessions as $session)<li>{{ $session->period() }}</li>@endforeach</ul>
        <p>Ausgestellt am {{ now()->format('d.m.Y') }}.</p>
        <p class="footer">{{ $footer['organization'] ?? 'Verband für Demokratiebildung und Bibliotheken an Schulen e. V.' }}<br>Diese Bestätigung wurde automatisch aus der Anwesenheitsliste erstellt.</p>
    </main>
</body>
</html>
