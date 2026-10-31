<!DOCTYPE html>
<html lang="de">
<body style="font-family: Arial, Helvetica, sans-serif; color: #0f172a; line-height: 1.5;">
    <p>Guten Tag,</p>
    @if ($kind === 'confirmed')
        <p>die Koordination hat Ihr Team für das Seminar <strong>{{ $seminar->title }}</strong> ({{ $seminar->type->label() }}) zusammengestellt: Sie sind dabei. Vielen Dank für Ihr Interesse!</p>
        <p>Schule: {{ $seminar->partner->name }}@if ($seminar->partner->city), {{ $seminar->partner->city }}@endif</p>
        @if ($seminar->sessions->isNotEmpty())
            <p>Termine:</p>
            <ul>@foreach ($seminar->sessions as $session)<li>{{ $session->period() }}@if ($session->location), {{ $session->location }}@endif</li>@endforeach</ul>
        @endif
        <p>Im Portal können Sie Ihre bestätigten Termine als Kalenderdatei herunterladen.</p>
    @elseif ($kind === 'cancelled')
        <p>Das Seminar <strong>{{ $seminar->title }}</strong> wurde abgesagt. Ihr Interesse bzw. Ihre Aufnahme ins Team ist damit hinfällig. Wir melden uns, wenn es einen neuen Termin gibt.</p>
    @else
        <p>Für das Seminar <strong>{{ $seminar->title }}</strong> haben wir das Team nach Kriterien wie Kompetenzen, Erfahrung und einer ausgewogenen Zusammensetzung zusammengestellt. Sie sind diesmal leider nicht dabei. Vielen Dank für Ihr Interesse, wir freuen uns auf die nächste Gelegenheit.</p>
    @endif
    <p><a href="{{ $url }}">Zu den Seminaren im Portal</a></p>
    <p>Verband für Demokratiebildung und Bibliotheken an Schulen e. V.</p>
</body>
</html>
