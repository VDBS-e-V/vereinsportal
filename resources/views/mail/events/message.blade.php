<!DOCTYPE html>
<html lang="de">
<body style="font-family: Arial, Helvetica, sans-serif; color: #0f172a; line-height: 1.5;">
    <p>Hallo,</p>
    <div>{!! rich_text($body) !!}</div>
    <hr style="border:0;border-top:1px solid #cbd5e1">
    <p style="font-size:13px;color:#475569">Diese Nachricht betrifft die Veranstaltung <strong>{{ $event->title }}</strong>. <a href="{{ $url }}">Zur Veranstaltungsseite</a></p>
    <p style="font-size:13px;color:#475569">Verband für Demokratiebildung und Bibliotheken an Schulen e. V.</p>
</body>
</html>
