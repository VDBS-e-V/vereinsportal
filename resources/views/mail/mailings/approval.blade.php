<!DOCTYPE html>
<html lang="de">
<body style="font-family: Arial, Helvetica, sans-serif; color: #0f172a; line-height: 1.5;">
    <p>Guten Tag,</p>
    <p>{{ $author }} hat im Portal ein Schreiben zur Freigabe vorgelegt.</p>
    <ul>
        <li>Betreff: <strong>{{ $mailing->subject }}</strong></li>
        <li>Empfänger: {{ $mailing->recipients_count }}</li>
        @if ($mailing->send_at)
            <li>Geplanter Versand: {{ $mailing->send_at->format('d.m.Y, H:i') }} Uhr</li>
        @endif
    </ul>
    <p><a href="{{ $url }}">Schreiben ansehen und freigeben</a></p>
    <p style="font-size:13px;color:#475569">Sie können diese Hinweise im Portal unter Konto › Benachrichtigungen abbestellen.</p>
</body>
</html>
