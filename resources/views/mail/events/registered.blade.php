<!DOCTYPE html>
<html lang="de">
<body style="font-family: Arial, Helvetica, sans-serif; color: #0f172a; line-height: 1.5;">
    <p>Hallo {{ $registration->name }},</p>
    <p>deine Anmeldung für <strong>{{ $event->title }}</strong> ist bestätigt.</p>
    <p>Dein Ticketcode: <strong style="font-size:18px;letter-spacing:2px;">{{ $registration->ticket() }}</strong><br>Bitte halte ihn am Veranstaltungstag bereit.</p>
    @if ($event->location)<p>Ort: {{ $event->location }}</p>@endif
    @if ($event->sessions->isNotEmpty())
        <p>Termine:</p>
        <ul>@foreach ($event->sessions as $session)<li>{{ $session->period() }}@if ($session->location), {{ $session->location }}@endif</li>@endforeach</ul>
    @endif
    <p>Für die Erlaubnis zur Teilnahme bist du selbst verantwortlich. An den Veranstaltungstagen führen wir Anwesenheitslisten. Danach kannst du dir hier eine Teilnahmebestätigung ausgeben lassen (mit Ticketcode und dieser E-Mail-Adresse):<br><a href="{{ $certificateUrl }}">{{ $certificateUrl }}</a></p>
    <p>Verband für Demokratiebildung und Bibliotheken an Schulen e. V.</p>
</body>
</html>
