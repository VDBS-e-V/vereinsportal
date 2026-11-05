<!DOCTYPE html>
<html lang="de">
<body style="font-family: Arial, Helvetica, sans-serif; color: #0f172a; line-height: 1.5;">
    <p>Guten Tag,</p>
    @if ($kind === 'request')
        <p>über das Portal ist eine neue Seminaranfrage der Schule <strong>{{ $seminar->partner->name }}</strong> eingegangen: {{ $seminar->title }}.</p>
    @elseif ($kind === 'confirmed')
        <p>Die Schule <strong>{{ $seminar->partner->name }}</strong> hat Termin und Kostenvoranschlag für das Seminar <strong>{{ $seminar->title }}</strong> bestätigt. Sie können das Seminar jetzt auf „Bestätigt“ setzen.</p>
    @else
        <p>Die Schule <strong>{{ $seminar->partner->name }}</strong> hat eine Nachricht zum Seminar <strong>{{ $seminar->title }}</strong> geschickt. Sie steht im Verlauf des Seminars.</p>
    @endif
    <p><a href="{{ $url }}">Zum Seminar im Portal</a></p>
    <p>Verband für Demokratiebildung und Bibliotheken an Schulen e. V.</p>
</body>
</html>
