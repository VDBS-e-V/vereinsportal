<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Aushang Feedback {{ $seminar->number }}</title>
    <style>
        @page { size: A4; margin: 20mm; }
        body { margin: 0; color: black; font: 14pt/1.5 "Segoe UI", Arial, sans-serif; text-align: center; }
        .toolbar { padding: 12px; font: 14px Arial, sans-serif; }
        .toolbar button { padding: 8px 16px; border: 2px solid black; background: black; color: white; font: inherit; cursor: pointer; }
        h1 { font-size: 28pt; margin: 16mm 0 6mm; }
        .url { display: inline-block; margin: 10mm 0; padding: 6mm 8mm; border: 3px solid black; font: bold 20pt/1.3 Consolas, monospace; word-break: break-all; }
        .small { font-size: 11pt; }
        .qr { width: 70mm; height: 70mm; margin: 6mm auto 0; }
        .qr svg { width: 100%; height: 100%; }
        @media print { .toolbar { display: none; } }
    </style>
</head>
<body>
    <div class="toolbar"><button type="button" onclick="window.print()">Drucken</button></div>
    <h1>Wie war das Seminar?</h1>
    <p>Wir freuen uns über deine Rückmeldung. Sie ist anonym und dauert etwa drei Minuten.</p>
    <div class="qr" id="qr" aria-hidden="true"></div>
    <p>Scanne den Code mit dem Handy oder gib diese Adresse im Browser ein:</p>
    <div class="url">{{ $link->url() }}</div>
    <p class="small">Offen vom {{ $link->opens_at->format('d.m.Y') }} bis {{ $link->closes_at->format('d.m.Y') }}.<br>{{ $seminar->title }}</p>
    <script src="{{ asset('vendor/qrcode/qrcode.js') }}"></script>
    <script>
        (function () {
            if (typeof qrcode !== 'function') { return; }
            var code = qrcode(0, 'M');
            code.addData({{ \Illuminate\Support\Js::from($link->url()) }});
            code.make();
            document.getElementById('qr').innerHTML = code.createSvgTag({ cellSize: 4, margin: 2, scalable: true });
        })();
    </script>
</body>
</html>
