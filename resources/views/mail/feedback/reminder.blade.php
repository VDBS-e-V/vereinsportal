<!DOCTYPE html>
<html lang="de">
<body style="font-family: Arial, Helvetica, sans-serif; color: #0f172a; line-height: 1.5;">
    <p>Guten Tag,</p>
    <p>zum Seminar <strong>{{ $seminar->number ? $seminar->number.' – ' : '' }}{{ $seminar->title }}</strong> würden wir uns über Ihre Rückmeldung freuen. Sie ist anonym und dauert wenige Minuten.</p>
    <p><a href="{{ $url }}">{{ $url }}</a></p>
    @if ($closesOn)
        <p>Der Link ist bis zum {{ $closesOn }} geöffnet.</p>
    @endif
    <p>Vielen Dank!<br>Ihr Verband</p>
</body>
</html>
