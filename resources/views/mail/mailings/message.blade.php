<!DOCTYPE html>
<html lang="de">
<body style="font-family: Arial, Helvetica, sans-serif; color: #0f172a; line-height: 1.5;">
    <div>{!! rich_text($bodyHtml) !!}</div>
    <hr style="border:0;border-top:1px solid #cbd5e1">
    <p style="font-size:13px;color:#475569">
        @if ($mandatory)
            Diese Nachricht ist eine Pflichtmitteilung des Verbands für Demokratiebildung und Bibliotheken an Schulen e. V. an seine Mitglieder und kann nicht abbestellt werden.
        @elseif ($kind === 'members')
            Sie erhalten diese Nachricht als Mitglied des Verbands für Demokratiebildung und Bibliotheken an Schulen e. V.
        @elseif ($kind === 'team')
            Sie erhalten diese Nachricht als Teamende:r des Verbands für Demokratiebildung und Bibliotheken an Schulen e. V.
        @elseif ($kind === 'schools')
            Sie erhalten diese Nachricht als Schulkonto im Portal des Verbands für Demokratiebildung und Bibliotheken an Schulen e. V.
        @else
            Diese Nachricht kommt vom Verband für Demokratiebildung und Bibliotheken an Schulen e. V.
        @endif
    </p>
    @if ($unsubscribeUrl)
        <p style="font-size:13px;color:#475569">Sie möchten solche Mitteilungen nicht mehr bekommen? <a href="{{ $unsubscribeUrl }}">Mitteilungen dieser Art abbestellen</a></p>
    @endif
</body>
</html>
