<!DOCTYPE html>
<html lang="de">
<body style="font-family: Arial, Helvetica, sans-serif; color: #0f172a; line-height: 1.5;">
    <p>Über das Kontaktformular des VDBS-Portals ist eine Nachricht für <strong>{{ $recipientLabel }}</strong> eingegangen.</p>
    <p><strong>Von:</strong> {{ $senderName }} ({{ $senderEmail }})<br><strong>Betreff:</strong> {{ $topic }}</p>
    <p style="white-space: pre-line;">{{ $body }}</p>
    <p>Mit „Antworten“ erreichen Sie die absendende Person direkt.</p>
</body>
</html>
