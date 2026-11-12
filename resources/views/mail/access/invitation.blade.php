<!DOCTYPE html>
<html lang="de">
<body style="font-family: Arial, Helvetica, sans-serif; color: #0f172a; line-height: 1.5;">
    <p>Guten Tag {{ $name }},</p>
    <p>Sie wurden ins Portal des Verbands für Demokratiebildung und Bibliotheken an Schulen e. V. eingeladen. Über diesen Link legen Sie Ihr Passwort fest und melden sich an:</p>
    <p><a href="{{ $url }}">Einladung annehmen und Passwort festlegen</a></p>
    <p>Der Link ist {{ $validDays }} Tage gültig. Wenn Sie die Einladung nicht erwartet haben, ignorieren Sie diese Nachricht.</p>
    <p>Verband für Demokratiebildung und Bibliotheken an Schulen e. V.</p>
</body>
</html>
