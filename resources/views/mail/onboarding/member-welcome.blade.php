<!DOCTYPE html>
<html lang="de">
<body style="font-family: Arial, Helvetica, sans-serif; color: #0f172a; line-height: 1.5;">
    <p>Guten Tag {{ $name }},</p>
    <p>herzlich willkommen im Verband für Demokratiebildung und Bibliotheken an Schulen!@if ($startsOn) Ihre Mitgliedschaft beginnt am {{ $startsOn }}.@endif</p>
    @if ($documents !== [])
        <p>Im Anhang dieser E-Mail finden Sie:</p>
        <ul>
            @foreach ($documents as $document)
                <li>{{ $document->kind->label() }}</li>
            @endforeach
        </ul>
    @endif
    @if ($portalUrl)
        <p>Mit Ihrem Konto im Vereinsportal können Sie Ihre Angaben einsehen und Ihr Profil pflegen: <a href="{{ $portalUrl }}">{{ $portalUrl }}</a></p>
    @endif
    <p>Bei Fragen melden Sie sich gern bei uns.</p>
    <p>Mit freundlichen Grüßen<br>Der Vorstand<br>Verband für Demokratiebildung und Bibliotheken an Schulen e. V.</p>
</body>
</html>
