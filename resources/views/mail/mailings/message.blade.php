<!DOCTYPE html>
<html lang="de">
<body style="font-family: Arial, Helvetica, sans-serif; color: #0f172a; line-height: 1.5;">
    <div>{!! rich_text($html) !!}</div>
    <hr style="border:0;border-top:1px solid #cbd5e1">
    <p style="font-size:13px;color:#475569">
        @if ($kind === 'members')
            Sie erhalten diese Nachricht als Mitglied des Verbands für Demokratiebildung und Bibliotheken an Schulen e. V.
        @else
            Sie erhalten diese Nachricht als Teamende:r des Verbands für Demokratiebildung und Bibliotheken an Schulen e. V. Sie können solche Mitteilungen im Portal unter Konto › Benachrichtigungen abbestellen.
        @endif
    </p>
</body>
</html>
