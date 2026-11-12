<!DOCTYPE html>
<html lang="de">
<body style="font-family: Arial, Helvetica, sans-serif; color: #0f172a; line-height: 1.5;">
    <p>Guten Tag {{ $guest->name }},</p>
    <p>der Vorstand des {{ $club }} lädt Sie als Gast zur Sitzung „{{ $meeting->title }}“ ein.</p>
    <p>
        <strong>Zeit:</strong> {{ $meeting->period() }}<br>
        @if (filled($meeting->location))<strong>Ort:</strong> {{ $meeting->location }}<br>@endif
        @if (filled($meeting->online_link))<strong>Videokonferenz:</strong> <a href="{{ $meeting->online_link }}">{{ $meeting->online_link }}</a><br>@endif
    </p>
    @if (filled($note))
        <p style="white-space: pre-line;">{{ $note }}</p>
    @endif
    @if (filled($agenda))
        <p><strong>Tagesordnung</strong></p>
        <p style="white-space: pre-line;">{{ $agenda }}</p>
    @endif
    <p>Im Anhang finden Sie den Termin als Kalenderdatei. Wenn Sie nicht teilnehmen können, antworten Sie bitte kurz auf diese E-Mail.</p>
    <p style="font-size: 13px; color: #475569;"><strong>Hinweis zum Datenschutz:</strong> Der {{ $club }} hat Ihren Namen und Ihre E-Mail-Adresse{{ filled($guest->organization) ? ' sowie Ihre Organisation' : '' }} vom Vorstand erhalten und speichert sie nur, um Sie zu dieser Sitzung einzuladen (Art. 6 Abs. 1 lit. f DSGVO). Nur der Vorstand sieht die Angaben; Sie erhalten keinen Zugang zum Portal. Wir löschen die Angaben {{ $retentionDays }} Tage nach der Sitzung. Sie haben das Recht auf Auskunft, Berichtigung, Löschung, Einschränkung und Widerspruch sowie auf Beschwerde bei einer Aufsichtsbehörde. @if (filled($privacyUrl))Mehr dazu in unserer <a href="{{ $privacyUrl }}">Datenschutzerklärung</a>.@endif</p>
    <p>{{ $club }}</p>
</body>
</html>
