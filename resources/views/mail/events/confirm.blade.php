<!DOCTYPE html>
<html lang="de">
<body style="font-family: Arial, Helvetica, sans-serif; color: #0f172a; line-height: 1.5;">
    <p>Hallo {{ $name }},</p>
    <p>du hast dich für die Veranstaltung <strong>{{ $event->title }}</strong> angemeldet. Bitte bestätige deine E-Mail-Adresse, damit deine Anmeldung gilt:</p>
    <p><a href="{{ $url }}">Anmeldung bestätigen</a></p>
    <p>Der Link ist {{ $hours }} Stunden gültig. Wenn du dich nicht angemeldet hast, ignoriere diese Nachricht.</p>
    <p>Verband für Demokratiebildung und Bibliotheken an Schulen e. V.</p>
</body>
</html>
