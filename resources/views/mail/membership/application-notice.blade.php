<!DOCTYPE html>
<html lang="de">
<body style="font-family: Arial, Helvetica, sans-serif; color: #0f172a; line-height: 1.5;">
    <p>Guten Tag,</p>
    <p>für diese E-Mail-Adresse wurde ein Antrag auf Mitgliedschaft im Verband für Demokratiebildung und Bibliotheken an Schulen e. V. gestellt. Wir konnten ihn nicht automatisch anlegen, weil zu Ihren Angaben schon ein Eintrag besteht.</p>
    @if ($hasAccount)
        <p>Sie haben bereits ein Konto im Portal. Sie können sich <a href="{{ $loginUrl }}">hier anmelden</a>. Falls Sie Ihr Passwort vergessen haben, <a href="{{ $resetUrl }}">setzen Sie es hier zurück</a>.</p>
    @else
        <p>Bitte wenden Sie sich an den Vorstand. Er kann Ihnen einen Registrierungslink für ein Online-Konto senden. Die Kontaktmöglichkeiten finden Sie <a href="{{ $contactUrl }}">hier</a>.</p>
    @endif
    <p>Wenn Sie den Antrag nicht gestellt haben, ignorieren Sie diese Nachricht.</p>
    <p>Verband für Demokratiebildung und Bibliotheken an Schulen e. V.</p>
</body>
</html>
