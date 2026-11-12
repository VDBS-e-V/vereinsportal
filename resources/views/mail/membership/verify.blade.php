<!DOCTYPE html>
<html lang="de">
<body style="font-family: Arial, Helvetica, sans-serif; color: #0f172a; line-height: 1.5;">
    <p>Guten Tag {{ $name }},</p>
    <p>vielen Dank für Ihren Antrag auf Mitgliedschaft im Verband für Demokratiebildung und Bibliotheken an Schulen e. V. Bitte bestätigen Sie Ihre E-Mail-Adresse, damit Ihr Konto im Portal aktiv wird:</p>
    <p><a href="{{ $url }}">E-Mail-Adresse bestätigen</a></p>
    <p>Der Link ist {{ $validHours }} Stunden gültig. Danach entscheidet der Vorstand über Ihren Antrag. Wenn Sie den Antrag nicht gestellt haben, ignorieren Sie diese Nachricht.</p>
    <p>Verband für Demokratiebildung und Bibliotheken an Schulen e. V.</p>
</body>
</html>
