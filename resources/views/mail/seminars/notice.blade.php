<!DOCTYPE html>
<html lang="de">
<body style="font-family: Arial, Helvetica, sans-serif; color: #0f172a; line-height: 1.5;">
    <p>Guten Tag{{ filled($name) ? ' '.$name : '' }},</p>
    @foreach ($paragraphs as $paragraph)
        <p>{{ $paragraph }}</p>
    @endforeach
    @if (filled($url))
        <p><a href="{{ $url }}">{{ $urlLabel ?: 'Im Vereinsportal öffnen' }}</a> (Anmeldung nötig)</p>
    @endif
    <p>Verband für Demokratiebildung und Bibliotheken an Schulen e. V.</p>
</body>
</html>
