<!DOCTYPE html>
<html lang="de">
<body style="font-family: Arial, Helvetica, sans-serif; color: #0f172a; line-height: 1.5;">
    <p>Guten Tag {{ $applicantName }},</p>
    <p>Sie haben sich für das Seminar <strong>{{ $seminar->title }}</strong> ({{ $seminar->type->label() }}) als Teamende:r beworben. Damit die Bewerbung zählt, bestätigen Sie bitte Ihre E-Mail-Adresse:</p>
    <p><a href="{{ $confirmUrl }}">Bewerbung bestätigen</a></p>
    <p style="color:#475569;font-size:14px">Der Link gilt 24 Stunden. Haben Sie sich nicht beworben, ignorieren Sie diese Nachricht; dann passiert nichts.</p>
    <p>Verband für Demokratiebildung und Bibliotheken an Schulen e. V.</p>
</body>
</html>
