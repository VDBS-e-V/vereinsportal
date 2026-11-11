<!DOCTYPE html>
<html lang="de">
<body style="font-family: Arial, Helvetica, sans-serif; color: #0f172a; line-height: 1.5;">
    <p>Guten Tag {{ $name }},</p>
    <p>der Anmeldeschluss für <strong>{{ $event->title }}</strong> ist am <strong>{{ $event->registration_deadline->format('d.m.Y') }}</strong>.
        @if ($ownCount > 0) Von Ihrer Schule sind bisher {{ $ownCount }} Schülerinnen und Schüler angemeldet. @else Von Ihrer Schule ist bisher noch niemand angemeldet. @endif
        Sie können Ihre Liste im Portal hochladen oder ergänzen.</p>
    <p><a href="{{ $url }}">Zur Veranstaltung im Portal</a></p>
    <p>Verband für Demokratiebildung und Bibliotheken an Schulen e. V.</p>
</body>
</html>
