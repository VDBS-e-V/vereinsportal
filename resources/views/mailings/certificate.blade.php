@php
    $sentAt = ($mailing->started_at ?? $mailing->queued_at)?->format('d.m.Y, H:i');
    $finishedAt = $mailing->finished_at?->format('d.m.Y, H:i');
    $rows = [
        ['Betreff', $mailing->subject],
        ['Gesendet von', $sender],
        ['Bereich', $mailing->sender_area === 'board' ? 'Vorstand' : 'Geschäftsstelle'],
        ['Gruppen', $groupLabels ?: 'nicht gespeichert'],
        ['Art', $mailing->mandatory ? 'Pflichtmitteilung (nicht abbestellbar)' : 'Mitteilung (abbestellbar)'],
        ['Freigabe', $approver ? $approver.' am '.$mailing->approved_at?->format('d.m.Y, H:i') : 'keine Freigabe nötig'],
        ['Versand gestartet', $sentAt ?? 'nicht gestartet'],
        ['Versand beendet', $finishedAt ?? 'noch nicht beendet'],
    ];
    $numbers = [
        ['Empfänger (Adressen)', $recipients],
        ['Zugestellt an den Versanddienst', $sent],
        ['Fehlgeschlagen', $failed],
        ['Offen oder übersprungen', $open],
        ['Wegen Abbestellung nicht angeschrieben', $unsubscribed ?? 'nicht erfasst'],
        ['Wegen gesperrter Adresse nicht angeschrieben', $suppressed ?? 'nicht erfasst'],
    ];
    $meetingRows = array_filter([['Versammlung', $meeting['versammlung']], ['Termin', $meeting['termin']], ['Ort', $meeting['ort']], ['Frist', $meeting['frist']]], fn ($row) => $row[1] !== '');
@endphp
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Versandbescheinigung</title>
    <style>
        body { font-family: system-ui, sans-serif; line-height: 1.5; max-width: 46rem; margin: 2rem auto; padding: 0 1rem; color: CanvasText; background: Canvas; }
        h1 { font-size: 1.6rem; margin-bottom: .25rem; }
        h2 { font-size: 1.1rem; margin: 1.75rem 0 .5rem; }
        table { border-collapse: collapse; width: 100%; }
        th, td { text-align: left; vertical-align: top; padding: .35rem .5rem; border-bottom: 1px solid GrayText; }
        th { width: 45%; font-weight: 600; }
        .text { border: 1px solid GrayText; padding: .75rem 1rem; }
        .note { font-size: .9rem; }
        .actions { margin-bottom: 1.5rem; display: flex; gap: .75rem; }
        @media print { .actions { display: none; } body { margin: 0; max-width: none; } }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" onclick="window.print()">Drucken</button>
        <a href="{{ route($base.'.show', $mailing) }}">Zurück zum Versand</a>
    </div>

    <h1>Versandbescheinigung</h1>
    <p>Schreiben Nr. {{ $mailing->id }}, ausgestellt am {{ $issuedAt->format('d.m.Y, H:i') }} Uhr aus dem Vereinsportal.</p>

    <h2>Angaben zum Versand</h2>
    <table>
        @foreach ($rows as [$label, $value])
            <tr><th scope="row">{{ $label }}</th><td>{{ $value }}</td></tr>
        @endforeach
    </table>

    @if ($meetingRows !== [])
        <h2>Angaben zur Versammlung</h2>
        <table>
            @foreach ($meetingRows as [$label, $value])
                <tr><th scope="row">{{ $label }}</th><td>{{ $value }}</td></tr>
            @endforeach
        </table>
    @endif

    <h2>Zahlen</h2>
    <table>
        @foreach ($numbers as [$label, $value])
            <tr><th scope="row">{{ $label }}</th><td>{{ $value }}</td></tr>
        @endforeach
    </table>

    <h2>Text des Schreibens</h2>
    <p class="note">Wie gesendet, mit den Platzhaltern vor dem Einsetzen der Namen und Angaben.</p>
    <div class="text">{!! rich_text($mailing->body) !!}</div>

    <p class="note" style="margin-top:1.5rem">Diese Bescheinigung protokolliert den Versand durch das Portal. Sie belegt nicht, dass die Nachricht bei einer einzelnen Person angekommen ist. Wer keine Adresse hat oder nicht zugestellt werden konnte, steht auf der Seite des Versands unter „Nicht erreichte Mitglieder“.</p>
</body>
</html>
