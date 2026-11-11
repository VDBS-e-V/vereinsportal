<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Terminübersicht {{ $seminar->number }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11pt; line-height: 1.4; margin: 2rem; color: #111; }
        h1 { font-size: 16pt; margin-bottom: 0.2rem; }
        table { border-collapse: collapse; width: 100%; margin-top: 1rem; }
        th, td { border: 1px solid #555; padding: 0.35rem 0.5rem; text-align: left; vertical-align: top; }
        .meta { color: #444; font-size: 9pt; }
    </style>
</head>
<body>
    <h1>Terminübersicht</h1>
    <p><strong>{{ $seminar->number }} – {{ $seminar->title }}</strong><br>{{ $seminar->type->label() }}, {{ $seminar->partner?->name }}@if ($seminar->partner?->city), {{ $seminar->partner->city }}@endif</p>
    <table>
        <thead><tr><th>Tag</th><th>Zeit</th><th>Ort</th><th>Hinweis</th></tr></thead>
        <tbody>
            @forelse ($seminar->sessions as $session)
                <tr>
                    <td>{{ $session->starts_at->copy()->locale('de')->translatedFormat('l, d.m.Y') }}</td>
                    <td>{{ $session->starts_at->format('H:i') }}–{{ $session->ends_at->format('H:i') }} Uhr</td>
                    <td>{{ $session->location ?: ($seminar->partner?->name ?? '') }}</td>
                    <td>{{ $session->notes }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Keine Termine eingetragen.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if ($seminar->location_notes)<p>Hinweise zum Ort: {{ $seminar->location_notes }}</p>@endif
    <p class="meta">Erstellt am {{ now()->format('d.m.Y H:i') }} aus dem Portal.</p>
</body>
</html>
