<!DOCTYPE html>
<html lang="de">
<body style="font-family: Arial, Helvetica, sans-serif; color: #0f172a; line-height: 1.5;">
    <p>Guten Tag,</p>
    <p>das Seminar <strong>{{ $seminar->title }}</strong>@if ($seminar->number) ({{ $seminar->number }})@endif ist vorbei. Für {{ $open }} {{ $open === 1 ? 'Vertrag fehlt' : 'Verträge fehlen' }} noch der unterschriebene Scan.</p>
    <p><a href="{{ $url }}">Zum Seminar im Portal</a></p>
    <p>Verband für Demokratiebildung und Bibliotheken an Schulen e. V.</p>
</body>
</html>
