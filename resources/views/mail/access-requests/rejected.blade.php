<!DOCTYPE html>
<html lang="de">
<body style="font-family: Arial, Helvetica, sans-serif; color: #0f172a; line-height: 1.5;">
    <p>Guten Tag {{ $name }},</p>
    <p>wir können Ihren Antrag auf Zugang zum VDBS-Portal leider nicht annehmen.</p>
    @if ($note)
        <p>Begründung: {{ $note }}</p>
    @endif
    <p>Bei Fragen melden Sie sich gern bei uns: <a href="{{ $contactUrl }}">{{ $contactUrl }}</a></p>
    <p>Verband für Demokratiebildung und Bibliotheken an Schulen e. V.</p>
</body>
</html>
