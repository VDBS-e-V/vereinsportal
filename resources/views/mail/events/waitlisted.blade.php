<!DOCTYPE html>
<html lang="de">
<body style="font-family: Arial, Helvetica, sans-serif; color: #0f172a; line-height: 1.5;">
    <p>Hallo {{ $registration->name }},</p>
    <p>die Veranstaltung <strong>{{ $event->title }}</strong> ist zurzeit ausgebucht. Deine Anmeldung ist bestätigt, du stehst aber auf der <strong>Warteliste</strong>. Wird ein Platz frei, rückst du automatisch nach und bekommst dann eine E-Mail mit deinem Ticketcode.</p>
    <p>Wenn du nicht mehr teilnehmen möchtest, kannst du dich hier abmelden (deine Daten werden dann gelöscht):<br><a href="{{ $cancelUrl }}">Abmelden</a></p>
    <p>Verband für Demokratiebildung und Bibliotheken an Schulen e. V.</p>
</body>
</html>
