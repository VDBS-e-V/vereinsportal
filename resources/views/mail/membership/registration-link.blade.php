<!DOCTYPE html>
<html lang="de">
<body style="font-family: Arial, Helvetica, sans-serif; color: #0f172a; line-height: 1.5;">
    <p>Guten Tag {{ $name }},</p>
    <p>der Verband für Demokratiebildung und Bibliotheken an Schulen e. V. lädt Sie ein, sich für das Vereinsportal zu registrieren. Über diesen Link legen Sie Ihr Konto selbst an und vergeben Ihr Passwort:</p>
    <p><a href="{{ $url }}">Konto registrieren</a></p>
    <p>Der Link ist {{ $validDays }} Tage gültig und nur für Sie bestimmt. Geben Sie ihn nicht weiter. Wenn Sie die Nachricht nicht erwartet haben, ignorieren Sie sie.</p>
    <p>Verband für Demokratiebildung und Bibliotheken an Schulen e. V.</p>
</body>
</html>
