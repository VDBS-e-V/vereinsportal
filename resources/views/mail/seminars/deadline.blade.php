<!DOCTYPE html>
<html lang="de">
<body style="font-family: Arial, Helvetica, sans-serif; color: #0f172a; line-height: 1.5;">
    <p>Guten Tag,</p>
    <p>der Stichtag ({{ $seminar->application_deadline->format('d.m.Y') }}) für das Seminar <strong>{{ $seminar->title }}</strong> ist vorbei.
        @if ($interested > 0) Es haben {{ $interested }} Teamende Interesse bekundet. Sie können die Interessenbekundungen jetzt sichten und das Team zusammenstellen (gesucht: {{ $seminar->team_needed }}).
        @else Es hat niemand Interesse bekundet. Sie können den Stichtag verschieben oder das Team direkt ansprechen.
        @endif
    </p>
    <p><a href="{{ $url }}">Zum Seminar im Portal</a></p>
    <p>Verband für Demokratiebildung und Bibliotheken an Schulen e. V.</p>
</body>
</html>
