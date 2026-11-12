<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Teamübersicht {{ $seminar->number }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11pt; line-height: 1.4; margin: 2rem; color: #111; }
        h1 { font-size: 16pt; margin-bottom: 0.2rem; }
        table { border-collapse: collapse; width: 100%; margin-top: 1rem; }
        th, td { border: 1px solid #555; padding: 0.35rem 0.5rem; text-align: left; vertical-align: top; }
        .meta { color: #444; font-size: 9pt; }
    </style>
</head>
<body>
    <h1>Teamübersicht</h1>
    <p><strong>{{ $seminar->number }} – {{ $seminar->title }}</strong><br>{{ $seminar->type->label() }}, {{ $seminar->partner?->name }}</p>
    <table>
        <thead><tr><th>Name</th><th>Kontakt</th></tr></thead>
        <tbody>
            @forelse ($team as $member)
                <tr>
                    <td>{{ $member['name'] }}</td>
                    <td>@if ($member['email']){{ $member['email'] }}@endif @if ($member['phone']){{ $member['email'] ? ', ' : '' }}{{ $member['phone'] }}@endif</td>
                </tr>
            @empty
                <tr><td colspan="2">Noch niemand im Team.</td></tr>
            @endforelse
        </tbody>
    </table>
    <p class="meta">Kontaktdaten stehen nur bei Personen, die der Weitergabe zugestimmt haben. Aufbewahrung: drei Jahre nach dem letzten Termin. Erstellt am {{ now()->format('d.m.Y H:i') }} aus dem Portal.</p>
</body>
</html>
