<!DOCTYPE html>
<html lang="de">
<body style="font-family: Arial, Helvetica, sans-serif; color: #0f172a; line-height: 1.5;">
    <p>Guten Tag{{ filled($seminar->contact_name) ? ' '.$seminar->contact_name : '' }},</p>
    <p>anbei erhalten Sie den Kostenvoranschlag {{ $seminar->number }} (Version {{ $quote->version }}) für das Seminar <strong>{{ $seminar->title }}</strong>.</p>
    <p>Sie können den Kostenvoranschlag im Portal annehmen, ablehnen oder uns eine Frage dazu schicken: <a href="{{ $url }}">Seminar im Vereinsportal öffnen</a> (Anmeldung nötig).</p>
    <p>Verband für Demokratiebildung und Bibliotheken an Schulen e. V.</p>
</body>
</html>
