<!DOCTYPE html>
<html lang="de">
<body style="font-family: Arial, Helvetica, sans-serif; color: #0f172a; line-height: 1.5;">
    <p>{{ $greetingName !== '' ? 'Guten Tag '.$greetingName.',' : 'Guten Tag,' }}</p>
    <p>mit Ihrem persönlichen Zugang sehen Sie die Seminare, für die der Verband für Demokratiebildung und Bibliotheken an Schulen e. V. ein Team sucht, und bewerben sich ohne eigenes Konto.</p>
    <p><strong>So melden Sie sich an:</strong></p>
    <p>Klicken Sie auf diesen Link:<br><a href="{{ $accessLink }}">{{ $accessLink }}</a></p>
    <p>Oder öffnen Sie <a href="{{ route('access.code') }}">{{ route('access.code') }}</a> und geben Sie dort Ihre E-Mail-Adresse und diesen Code ein:<br><strong style="font-size:20px;letter-spacing:2px">{{ $accessCode }}</strong></p>
    <p style="color:#475569;font-size:14px">Der Zugang gehört nur Ihnen. Bitte geben Sie Link und Code nicht weiter. Bei Fragen am Telefon nennen wir Ihnen nur die letzten zwei Zeichen des Codes zur Kontrolle: …{{ $codeHint }}</p>
    <hr style="border:0;border-top:1px solid #cbd5e1">
    <p style="font-size:13px;color:#475569">Sie erhalten diese Nachricht, weil Ihre Adresse auf der Mailingliste des Verbands für Demokratiebildung und Bibliotheken an Schulen e. V. steht. <a href="{{ $unsubscribeUrl }}">Mitteilungen dieser Art abbestellen</a></p>
</body>
</html>
