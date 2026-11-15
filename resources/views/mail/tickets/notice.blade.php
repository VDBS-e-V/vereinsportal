<!DOCTYPE html>
<html lang="de">
<body style="font-family: Arial, Helvetica, sans-serif; color: #0f172a; line-height: 1.5;">
    <p>Guten Tag,</p>
    @switch($kind)
        @case('new')
            <p>im Vereinsportal wurde ein neues Ticket angelegt{{ $by ? ' von '.$by : '' }}: <strong>{{ $reference }} – {{ $subjectLine }}</strong>.</p>
            @break
        @case('assigned')
            <p>{{ $by ?: 'Das Team' }} hat Ihnen das Ticket <strong>{{ $reference }} – {{ $subjectLine }}</strong> zugewiesen.</p>
            @break
        @case('requester_reply')
            <p>{{ $by ?: 'Die anfragende Person' }} hat im Ticket <strong>{{ $reference }} – {{ $subjectLine }}</strong> geschrieben.</p>
            @break
        @default
            <p>das Team hat auf Ihr Ticket <strong>{{ $reference }} – {{ $subjectLine }}</strong> geantwortet.</p>
    @endswitch
    <p><a href="{{ $url }}">Ticket im Vereinsportal öffnen</a> (Anmeldung nötig)</p>
    <p style="font-size: 13px; color: #475569;">Aus Datenschutzgründen steht der Text der Nachricht nur im Portal.@if (in_array($kind, ['new', 'assigned', 'requester_reply'], true)) Diese Hinweise können Sie in Ihrem Konto unter „Benachrichtigungen“ ausschalten.@endif</p>
    <p>Verband für Demokratiebildung und Bibliotheken an Schulen e. V.</p>
</body>
</html>
