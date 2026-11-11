<!DOCTYPE html>
<html lang="de">
<body style="font-family: Arial, Helvetica, sans-serif; color: #0f172a; line-height: 1.5;">
    <p>Hallo {{ $registration->name }},</p>
    <p>bald ist es so weit: <strong>{{ $event->title }}</strong> findet statt.</p>
    @if ($event->sessions->isNotEmpty())
        <ul>@foreach ($event->sessions as $session)<li>{{ $session->period() }}@if ($session->location ?? $event->location), {{ $session->location ?? $event->location }}@endif</li>@endforeach</ul>
    @endif
    <p>Dein Ticketcode: <strong style="font-size:18px;letter-spacing:2px;">{{ $registration->ticket() }}</strong></p>
    <p>Kannst du doch nicht kommen? Dann melde dich bitte ab, damit jemand anderes nachrücken kann:<br><a href="{{ $cancelUrl }}">Abmelden</a></p>
    <p>Verband für Demokratiebildung und Bibliotheken an Schulen e. V.</p>
</body>
</html>
